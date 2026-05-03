<?php

namespace App\Services;

use App\Models\Ticket;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SlackNotifier
{
    private static array $columnLabels = [
        'to_do'             => '📋 To Do',
        'in_progress'       => '🔄 In Progress',
        'blocked'           => '🚫 Blocked',
        'code_review'       => '🔍 Code Review',
        'qa_testing'        => '🔬 QA Testing',
        'ready_for_release' => '🚀 Ready for Release',
        'done'              => '✅ Done',
    ];

    private static array $columnColors = [
        'to_do'             => '#607D8B',
        'in_progress'       => '#FF9800',
        'blocked'           => '#F44336',
        'code_review'       => '#9C27B0',
        'qa_testing'        => '#00BCD4',
        'ready_for_release' => '#8BC34A',
        'done'              => '#4CAF50',
    ];

    /**
     * Returns a Slack-formatted clickable link for a ticket.
     * Format: <https://hub.test/tickets/5|[T-0005]>
     */
    private static function ticketLink(Ticket $ticket): string
    {
        $url = rtrim(config('app.url'), '/') . '/tickets/' . $ticket->id;
        return "<{$url}|[{$ticket->ticket_number}]>";
    }

    /**
     * Notify Slack when a new ticket is received from ERP.
     */
    public static function ticketCreated(Ticket $ticket): void
    {
        $link         = self::ticketLink($ticket);
        $assigneeName = $ticket->assignee ? $ticket->assignee->name : 'Sin asignar';
        $moduleLine   = $ticket->module . ($ticket->sub_module ? ' › ' . $ticket->sub_module : '');

        self::send([
            'text'        => "🎫 Nuevo ticket recibido: *{$link} {$ticket->subject}*",
            'attachments' => [[
                'color'  => '#2196F3',
                'fields' => [
                    ['title' => 'Cliente',       'value' => $ticket->client ? $ticket->client->business_name : 'Interno', 'short' => true],
                    ['title' => 'Reportado por', 'value' => $ticket->reporter_name,         'short' => true],
                    ['title' => 'Módulo',        'value' => $moduleLine ?: '—',             'short' => true],
                    ['title' => 'Asignado a',    'value' => $assigneeName,                 'short' => true],
                ],
                'footer' => 'Support Hub',
                'ts'     => now()->timestamp,
            ]],
        ]);
    }

    /**
     * Notify Slack when an internal ticket is created.
     */
    public static function internalTicketCreated(Ticket $ticket): void
    {
        $link         = self::ticketLink($ticket);
        $assigneeName = $ticket->assignee ? $ticket->assignee->name : 'Sin asignar';
        $typeBadges   = ['bug' => '🐛', 'feature_request' => '💡', 'task' => '📝', 'configuration' => '⚙️', 'question' => '❓'];
        $typeIcon     = $typeBadges[$ticket->ticket_type] ?? '📋';

        self::send([
            'text'        => "{$typeIcon} *{$ticket->reporter_name}* creó ticket interno: *{$link} {$ticket->subject}*",
            'attachments' => [[
                'color'  => '#28a745',
                'fields' => [
                    ['title' => 'Tipo',        'value' => ucfirst(str_replace('_', ' ', $ticket->ticket_type)), 'short' => true],
                    ['title' => 'Prioridad',   'value' => strtoupper($ticket->issue_priority),                  'short' => true],
                    ['title' => 'Módulo',      'value' => $ticket->module ? ucfirst($ticket->module) : '—',     'short' => true],
                    ['title' => 'Asignado a',  'value' => $assigneeName,                                        'short' => true],
                ],
                'footer' => 'Support Hub · Ticket interno',
                'ts'     => now()->timestamp,
            ]],
        ]);
    }

    /**
     * Notify Slack when a ticket is moved to a different board column.
     */
    public static function ticketMoved(Ticket $ticket, string $oldColumn, string $newColumn, string $movedBy): void
    {
        $link     = self::ticketLink($ticket);
        $oldLabel = self::$columnLabels[$oldColumn] ?? $oldColumn;
        $newLabel = self::$columnLabels[$newColumn] ?? $newColumn;
        $color    = self::$columnColors[$newColumn] ?? '#607D8B';

        if ($newColumn === 'done') {
            $text   = "✅ *{$movedBy}* resolvió *{$link} {$ticket->subject}*";
            $fields = [
                ['title' => $ticket->isInternal() ? 'Origen' : 'Cliente', 'value' => $ticket->client ? $ticket->client->business_name : 'Interno', 'short' => true],
                ['title' => 'Columna',  'value' => "{$oldLabel} → {$newLabel}",    'short' => true],
            ];
        } else {
            $text   = "*{$movedBy}* movió *{$link}* de *{$oldLabel}* → *{$newLabel}*";
            $fields = [
                ['title' => 'Asunto',     'value' => $ticket->subject,               'short' => false],
                ['title' => 'Cliente',    'value' => $ticket->client ? $ticket->client->business_name : 'Interno', 'short' => true],
                ['title' => 'Asignado a', 'value' => $ticket->assignee ? $ticket->assignee->name : 'Sin asignar', 'short' => true],
            ];
        }

        self::send([
            'text'        => $text,
            'attachments' => [[
                'color'  => $color,
                'fields' => $fields,
                'footer' => 'Support Hub · ' . $ticket->ticket_number,
                'ts'     => now()->timestamp,
            ]],
        ]);
    }

    /**
     * Notify Slack when the client replies from ERP.
     */
    public static function clientReplied(Ticket $ticket, string $message): void
    {
        $link = self::ticketLink($ticket);

        self::send([
            'text'        => "💬 *{$ticket->reporter_name}* respondió en *{$link} {$ticket->subject}*",
            'attachments' => [[
                'color'  => '#9C27B0',
                'fields' => [
                    ['title' => 'Cliente',    'value' => $ticket->client ? $ticket->client->business_name : 'Interno', 'short' => true],
                    ['title' => 'Asignado a', 'value' => $ticket->assignee ? $ticket->assignee->name : 'Sin asignar', 'short' => true],
                ],
                'footer' => 'Support Hub · ' . $ticket->ticket_number,
                'ts'     => now()->timestamp,
            ]],
        ]);
    }

    /**
     * Notify Slack when a ticket is assigned or reassigned to a dev.
     */
    public static function ticketAssigned(Ticket $ticket, string $assigneeName, string $assignedBy): void
    {
        $link = self::ticketLink($ticket);

        self::send([
            'text'        => "📌 *{$assignedBy}* asignó *{$link}* a *{$assigneeName}*",
            'attachments' => [[
                'color'  => '#607D8B',
                'fields' => [
                    ['title' => 'Asunto',  'value' => $ticket->subject,                                                'short' => false],
                    ['title' => $ticket->isInternal() ? 'Origen' : 'Cliente', 'value' => $ticket->client ? $ticket->client->business_name : 'Interno', 'short' => true],
                    ['title' => 'Módulo',  'value' => $ticket->module ?: '—',                                            'short' => true],
                ],
                'footer' => 'Support Hub · ' . $ticket->ticket_number,
                'ts'     => now()->timestamp,
            ]],
        ]);
    }

    private static function send(array $payload): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (!$webhookUrl) {
            return;
        }

        try {
            $http = new Client(['timeout' => 5]);
            $http->post($webhookUrl, ['json' => $payload]);
        } catch (\Exception $e) {
            Log::warning('Slack notification failed: ' . $e->getMessage());
        }
    }
}
