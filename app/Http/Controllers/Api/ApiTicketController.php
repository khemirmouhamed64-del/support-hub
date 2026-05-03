<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTicketRequest;
use App\Http\Requests\Api\UpdateTicketStatusRequest;
use App\Models\Client;
use App\Models\HubNotification;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Jobs\ClassifyTicketJob;
use App\Services\SlackNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiTicketController extends Controller
{
    /**
     * POST /api/tickets
     * Receive a new ticket from an ERP instance.
     */
    public function store(StoreTicketRequest $request)
    {
        $client = $request->attributes->get('client');
        $data = $request->validated();

        try {
            $ticket = DB::transaction(function () use ($data, $client) {
                $ticket = Ticket::create([
                    'client_id'          => $client->id,
                    'external_ticket_id' => $data['external_ticket_id'] ?? null,
                    'ticket_number'      => Ticket::generateTicketNumber(),
                    'reporter_name'      => $data['reporter_name'],
                    'reporter_email'     => $data['reporter_email'] ?? null,
                    'ticket_type'        => $data['ticket_type'],
                    'module'             => $data['module'],
                    'sub_module'         => $data['sub_module'] ?? null,
                    'issue_priority'     => $data['priority'] ?? 'medium',
                    'client_priority'    => $client->priority_level,
                    'subject'            => $data['subject'],
                    'description'        => $data['description'],
                    'steps_to_reproduce' => $data['steps_to_reproduce'] ?? null,
                    'expected_behavior'  => $data['expected_behavior'] ?? null,
                    'browser_url'        => $data['browser_url'] ?? null,
                    'board_column'       => 'to_do',
                ]);

                $ticket->calculateEffectivePriority();
                $ticket->save();

                // Save attachments
                if (!empty($data['attachments'])) {
                    $this->saveAttachments($ticket, $data['attachments']);
                }

                // Initial status history
                TicketStatusHistory::create([
                    'ticket_id'  => $ticket->id,
                    'old_column' => null,
                    'new_column' => 'to_do',
                    'notes'      => 'Ticket received from ERP.',
                    'created_at' => now(),
                ]);

                // Suggest assignee
                $suggested = $ticket->suggestAssignee();
                if ($suggested) {
                    $ticket->update(['assigned_to' => $suggested->id]);

                    TicketStatusHistory::create([
                        'ticket_id'  => $ticket->id,
                        'old_column' => 'to_do',
                        'new_column' => 'to_do',
                        'changed_by' => null,
                        'notes'      => 'Auto-assigned to ' . $suggested->name . ' (module expertise).',
                        'created_at' => now(),
                    ]);

                    HubNotification::create([
                        'recipient_id' => $suggested->id,
                        'ticket_id'    => $ticket->id,
                        'type'         => 'assignment',
                        'title'        => 'New ticket assigned: ' . $ticket->ticket_number,
                        'message'      => $ticket->subject,
                        'created_at'   => now(),
                    ]);
                }

                // Notify all active leads/PMs of new ticket
                TeamMember::active()
                    ->whereIn('role', ['lead', 'pm'])
                    ->where('id', '!=', optional($suggested)->id)
                    ->each(function ($member) use ($ticket) {
                        HubNotification::create([
                            'recipient_id' => $member->id,
                            'ticket_id'    => $ticket->id,
                            'type'         => 'new_ticket',
                            'title'        => 'New ticket: ' . $ticket->ticket_number,
                            'message'      => '[' . $ticket->client->business_name . '] ' . $ticket->subject,
                            'created_at'   => now(),
                        ]);
                    });

                return $ticket;
            });

            $ticket->load('client', 'assignee');
            SlackNotifier::ticketCreated($ticket);

            // Classify after the response is sent — does not block the ERP webhook
            ClassifyTicketJob::dispatchAfterResponse($ticket);

            return response()->json([
                'success'       => true,
                'ticket_number' => $ticket->ticket_number,
                'hub_ticket_id' => $ticket->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('API store ticket failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'Failed to create ticket.',
            ], 500);
        }
    }

    /**
     * POST /api/tickets/{id}/status
     * Update ticket board column and optionally notify the ERP.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, $id)
    {
        $client = $request->attributes->get('client');
        $ticket = Ticket::where('id', $id)->where('client_id', $client->id)->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'error' => 'Ticket not found.'], 404);
        }

        $data = $request->validated();
        $oldColumn = $ticket->board_column;
        $newColumn = $data['board_column'];

        $ticket->board_column = $newColumn;

        if (isset($data['assigned_to'])) {
            $ticket->assigned_to = $data['assigned_to'];
        }

        if ($newColumn === 'done' && !empty($data['resolution_message'])) {
            $ticket->resolution_message = $data['resolution_message'];
            $ticket->resolved_at = now();
        }

        $ticket->save();

        // Status history
        TicketStatusHistory::create([
            'ticket_id'  => $ticket->id,
            'old_column' => $oldColumn,
            'new_column' => $newColumn,
            'notes'      => $data['notes'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json([
            'success'      => true,
            'hub_ticket_id' => $ticket->id,
            'old_column'   => $oldColumn,
            'new_column'   => $newColumn,
        ]);
    }

    /**
     * Save base64 or URL-referenced attachments.
     */
    private function saveAttachments(Ticket $ticket, array $attachments)
    {
        foreach ($attachments as $file) {
            $path = null;

            if (!empty($file['base64'])) {
                $extension = $this->guessExtension($file['name']);
                $filename = Str::random(20) . '.' . $extension;
                $dir = 'ticket_attachments/' . $ticket->id;
                Storage::disk('public')->put($dir . '/' . $filename, base64_decode($file['base64']));
                $path = $dir . '/' . $filename;
            } elseif (!empty($file['url'])) {
                $path = $file['url'];
            }

            if ($path) {
                TicketAttachment::create([
                    'ticket_id'  => $ticket->id,
                    'file_name'  => $file['name'],
                    'file_path'  => $path,
                    'file_type'  => $file['type'],
                    'file_size'  => !empty($file['base64']) ? strlen(base64_decode($file['base64'])) : null,
                    'source'     => 'client',
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * POST /api/tickets/{id}/client-response
     * Receive a client response from the ERP.
     */
    public function clientResponse(Request $request, $id)
    {
        $client = $request->attributes->get('client');
        $ticket = Ticket::where('id', $id)->where('client_id', $client->id)->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'error' => 'Ticket not found.'], 404);
        }

        $message = $request->input('message', '');
        $respondedBy = $request->input('responded_by', 'Cliente');
        $attachments = $request->input('attachments', []);

        if (empty(trim($message)) && empty($attachments)) {
            return response()->json(['success' => false, 'error' => 'Message or attachments required.'], 422);
        }

        try {
            $comment = DB::transaction(function () use ($ticket, $message, $respondedBy, $attachments) {
                // Create comment visible to the team
                $comment = TicketComment::create([
                    'ticket_id'   => $ticket->id,
                    'author_id'   => null,
                    'author_name' => $respondedBy,
                    'visibility'  => 'client',
                    'source'      => 'client',
                    'content'     => $message,
                ]);

                // Save attachments linked to the comment
                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        $path = null;

                        if (!empty($file['base64'])) {
                            $extension = $this->guessExtension($file['name']);
                            $filename = Str::random(20) . '.' . $extension;
                            $dir = 'ticket_attachments/' . $ticket->id;
                            Storage::disk('public')->put($dir . '/' . $filename, base64_decode($file['base64']));
                            $path = $dir . '/' . $filename;
                        }

                        if ($path) {
                            TicketAttachment::create([
                                'ticket_id'  => $ticket->id,
                                'comment_id' => $comment->id,
                                'file_name'  => $file['name'],
                                'file_path'  => $path,
                                'file_type'  => $file['type'] ?? 'document',
                                'file_size'  => !empty($file['base64']) ? strlen(base64_decode($file['base64'])) : null,
                                'source'     => 'client',
                                'created_at' => now(),
                            ]);
                        }
                    }
                }

                // Notify the assigned dev
                if ($ticket->assigned_to) {
                    HubNotification::create([
                        'recipient_id' => $ticket->assigned_to,
                        'ticket_id'    => $ticket->id,
                        'type'         => 'client_response',
                        'title'        => 'Client replied: ' . $ticket->ticket_number,
                        'message'      => Str::limit($message, 120),
                        'created_at'   => now(),
                    ]);
                }

                // Also notify leads/PMs
                TeamMember::active()
                    ->whereIn('role', ['lead', 'pm'])
                    ->where('id', '!=', $ticket->assigned_to)
                    ->each(function ($member) use ($ticket, $message) {
                        HubNotification::create([
                            'recipient_id' => $member->id,
                            'ticket_id'    => $ticket->id,
                            'type'         => 'client_response',
                            'title'        => 'Client replied: ' . $ticket->ticket_number,
                            'message'      => Str::limit($message, 120),
                            'created_at'   => now(),
                        ]);
                    });

                return $comment;
            });

            $ticket->load('client', 'assignee');
            SlackNotifier::clientReplied($ticket, $message);

            return response()->json([
                'success'    => true,
                'comment_id' => $comment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('API client response failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'Failed to save client response.',
            ], 500);
        }
    }

    /**
     * POST /api/tickets/{id}/delete
     * Soft-delete a ticket when requested by the ERP.
     */
    public function deleteTicket(Request $request, $id)
    {
        $client = $request->attributes->get('client');
        $ticket = Ticket::where('id', $id)->where('client_id', $client->id)->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'error' => 'Ticket not found.'], 404);
        }

        if (!$ticket->archived_at) {
            $ticket->update(['archived_at' => now()]);
        }

        $ticket->delete();

        return response()->json(['success' => true]);
    }

    private function guessExtension($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return $ext ?: 'bin';
    }

    /**
     * GET /api/ping — validates API key and returns client info.
     * Used by the ERP to verify the connection from its settings page.
     */
    public function ping(Request $request)
    {
        $client = $request->attributes->get('client');

        return response()->json([
            'success'    => true,
            'message'    => 'Connection OK',
            'client'     => $client->business_name,
            'identifier' => $client->client_identifier,
        ]);
    }
}
