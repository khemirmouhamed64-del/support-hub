<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\HubNotification;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketMention;
use App\Models\TicketStatusHistory;
use App\Services\SlackNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function kanban(Request $request)
    {
        $clients = Client::active()->orderBy('business_name')->get();
        $members = TeamMember::active()->orderBy('name')->get();
        $modules = array_column(config('support.modules', []), 'key');

        return view('tickets.kanban', compact('clients', 'members', 'modules'));
    }

    /**
     * AJAX: create an internal ticket (not from a client ERP).
     */
    public function storeInternal(Request $request)
    {
        $request->validate([
            'subject'        => 'required|string|max:255',
            'description'    => 'required|string',
            'ticket_type'    => 'required|in:bug,feature_request,task,configuration,question',
            'module'         => 'nullable|string|max:100',
            'issue_priority' => 'required|in:low,medium,high,critical',
            'assigned_to'    => 'nullable|exists:team_members,id',
            'due_date'       => 'nullable|date|after_or_equal:today',
            'board_column'   => 'nullable|in:' . implode(',', Ticket::BOARD_COLUMNS),
            'label_ids'      => 'nullable|array',
            'label_ids.*'    => 'exists:labels,id',
        ]);

        $priorityMap = ['critical' => 1, 'high' => 3, 'medium' => 5, 'low' => 7];

        $ticket = Ticket::create([
            'source'             => 'internal',
            'client_id'          => null,
            'ticket_number'      => Ticket::generateTicketNumber(),
            'reporter_name'      => Auth::user()->name,
            'reporter_email'     => Auth::user()->email,
            'ticket_type'        => $request->ticket_type,
            'module'             => $request->module,
            'subject'            => $request->subject,
            'description'        => $request->description,
            'issue_priority'     => $request->issue_priority,
            'effective_priority' => $priorityMap[$request->issue_priority] ?? 5,
            'board_column'       => $request->board_column ?? 'to_do',
            'assigned_to'        => $request->assigned_to,
            'due_date'           => $request->due_date,
            'ai_status'          => 'manual',
        ]);

        if (!empty($request->label_ids)) {
            $ticket->labels()->sync($request->label_ids);
        }

        TicketStatusHistory::create([
            'ticket_id'  => $ticket->id,
            'old_column' => null,
            'new_column' => $ticket->board_column,
            'changed_by' => Auth::id(),
            'notes'      => 'Internal ticket created',
            'created_at' => now(),
        ]);

        if ($request->assigned_to && $request->assigned_to != Auth::id()) {
            HubNotification::create([
                'recipient_id' => $request->assigned_to,
                'ticket_id'    => $ticket->id,
                'type'         => 'assignment',
                'title'        => __('tickets.notif_assigned', ['ticket' => $ticket->ticket_number]),
                'message'      => __('tickets.notif_assigned_by', ['name' => Auth::user()->name]),
                'created_at'   => now(),
            ]);
        }

        // Save uploaded attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $dir = 'attachments/tickets/' . $ticket->id;
                $path = $file->store($dir, 'public');
                $ext = strtolower($file->getClientOriginalExtension());
                $imageExts = ['jpg','jpeg','png','gif','bmp','webp','svg'];
                $videoExts = ['mp4','mov','avi','webm'];

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => in_array($ext, $imageExts) ? 'image' : (in_array($ext, $videoExts) ? 'video' : 'document'),
                    'file_size' => $file->getSize(),
                    'source'    => 'dev',
                ]);
            }
        }

        $ticket->load('assignee');
        SlackNotifier::internalTicketCreated($ticket);

        return response()->json([
            'success' => true,
            'ticket'  => $ticket,
            'message' => __('tickets.internal_ticket_created'),
        ]);
    }

    /**
     * AJAX: return tickets grouped by board_column, with optional filters.
     */
    public function boardData(Request $request)
    {
        $query = Ticket::with(['client', 'assignee', 'labels'])
            ->notArchived()
            ->orderBy('effective_priority', 'asc')
            ->orderBy('created_at', 'asc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('priority')) {
            $query->where('incident_type', $request->priority);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $tickets = $query->get();

        // Unread client_response notifications for current user, grouped by ticket
        $unreadClientReplies = HubNotification::where('recipient_id', Auth::id())
            ->where('type', 'client_response')
            ->whereNull('read_at')
            ->selectRaw('ticket_id, COUNT(*) as cnt')
            ->groupBy('ticket_id')
            ->pluck('cnt', 'ticket_id');

        $columns = [];
        foreach (Ticket::BOARD_COLUMNS as $col) {
            $columns[$col] = $tickets->where('board_column', $col)->values()->map(function ($t) use ($unreadClientReplies) {
                return [
                    'id'                      => $t->id,
                    'ticket_number'           => $t->ticket_number,
                    'subject'                 => $t->subject,
                    'ticket_type'             => $t->ticket_type,
                    'module'                  => $t->module,
                    'source'                  => $t->source,
                    'issue_priority'          => $t->issue_priority,
                    'effective_priority'      => $t->effective_priority,
                    'client_name'             => $t->client ? $t->client->business_name : null,
                    'client_priority'         => $t->client_priority,
                    'assignee_name'           => $t->assignee ? $t->assignee->name : null,
                    'assignee_initials'       => $t->assignee ? $this->initials($t->assignee->name) : null,
                    'created_at'              => $t->created_at->format('M d, H:i'),
                    'time_in_column'          => $this->timeInColumn($t),
                    'due_date'                => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'unread_client_replies'   => isset($unreadClientReplies[$t->id]) ? $unreadClientReplies[$t->id] : 0,
                    'incident_type'           => $t->incident_type,
                    'ai_status'               => $t->ai_status,
                    'labels'                  => $t->labels->map(function ($l) {
                        return ['id' => $l->id, 'name' => $l->name, 'color' => $l->color];
                    }),
                ];
            });
        }

        return response()->json($columns);
    }

    /**
     * AJAX: move ticket between columns (drag & drop).
     */
    public function moveColumn(Request $request, Ticket $ticket)
    {
        $request->validate([
            'board_column' => 'required|in:' . implode(',', Ticket::BOARD_COLUMNS),
        ]);

        $oldColumn = $ticket->board_column;
        $newColumn = $request->board_column;

        if ($oldColumn === $newColumn) {
            return response()->json(['success' => true, 'changed' => false]);
        }

        // ERP-first: notify ERP before saving locally. Skip for internal tickets.
        if (!$ticket->isInternal()) {
            $erpOk = $this->notifyErp($ticket, $oldColumn, $newColumn);
            if (!$erpOk) {
                return response()->json([
                    'success'   => false,
                    'erp_error' => true,
                    'message'   => __('tickets.erp_move_blocked'),
                ], 503);
            }
        }

        $ticket->board_column = $newColumn;

        if ($newColumn === 'done' && !$ticket->resolved_at) {
            $ticket->resolved_at = now();
        }

        $ticket->save();

        TicketStatusHistory::create([
            'ticket_id'  => $ticket->id,
            'old_column' => $oldColumn,
            'new_column' => $newColumn,
            'changed_by' => Auth::id(),
            'notes'      => null,
            'created_at' => now(),
        ]);

        // Notify assignee if moved by someone else
        if ($ticket->assigned_to && $ticket->assigned_to !== Auth::id()) {
            HubNotification::create([
                'recipient_id' => $ticket->assigned_to,
                'ticket_id'    => $ticket->id,
                'type'         => 'status_change',
                'title'        => __('tickets.notif_moved', ['ticket' => $ticket->ticket_number, 'column' => $ticket->boardColumnLabel()]),
                'message'      => __('tickets.notif_moved_by', ['name' => Auth::user()->name]),
                'created_at'   => now(),
            ]);
        }

        SlackNotifier::ticketMoved($ticket, $oldColumn, $newColumn, Auth::user()->name);

        return response()->json([
            'success'    => true,
            'changed'    => true,
            'old_column' => $oldColumn,
            'new_column' => $newColumn,
        ]);
    }

    /**
     * AJAX: save resolution message before marking done.
     */
    public function resolve(Request $request, Ticket $ticket)
    {
        $request->validate([
            'resolution_message' => 'nullable|string',
        ]);

        $ticket->update([
            'resolution_message' => $request->resolution_message,
        ]);

        // Save as client-facing comment (only for client tickets)
        if (!empty($request->resolution_message) && !$ticket->isInternal()) {
            TicketComment::create([
                'ticket_id'   => $ticket->id,
                'author_id'   => Auth::id(),
                'author_name' => Auth::user()->name,
                'visibility'  => 'client',
                'content'     => $request->resolution_message,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * All tickets list view.
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['client', 'assignee'])
            ->notArchived()
            ->orderBy('effective_priority', 'asc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('board_column', $request->status);
        }

        $tickets = $query->paginate(25);
        return view('tickets.index', compact('tickets'));
    }

    /**
     * Ticket detail view.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'client',
            'assignee',
            'attachments',
            'prLinks',
            'commits',
            'labels',
            'comments' => function ($q) { $q->with(['author', 'mentions.mentionedMember', 'attachments'])->orderBy('created_at', 'asc'); },
            'statusHistory' => function ($q) { $q->with('changedByMember')->orderBy('created_at', 'desc'); },
        ]);

        $members = TeamMember::active()->orderBy('name')->get();

        // Suggested assignee based on module expertise
        $suggested = $ticket->assigned_to ? null : $ticket->suggestAssignee();

        return view('tickets.show', compact('ticket', 'members', 'suggested'));
    }

    /**
     * AJAX: add comment (internal or client-facing) with optional file attachments.
     * For client-facing comments: ERP-first flow — send to ERP before saving locally.
     */
    public function addComment(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'content'    => 'nullable|string',
            'visibility' => 'required|in:internal,client',
        ]);

        $htmlContent = $data['content'] ?? '';

        // Sanitize HTML: strip disallowed tags and dangerous attributes
        $htmlContent = $this->sanitizeHtml($htmlContent);

        // Remove empty Summernote default content
        $textOnly = trim(strip_tags($htmlContent));
        $hasImages = strpos($htmlContent, '<img') !== false;

        if (empty($textOnly) && !$hasImages) {
            return response()->json(['success' => false, 'error' => __('tickets.comment_empty')], 422);
        }

        // ERP-first: for client-facing comments, send plain text + HTML + attachments to ERP
        $erpResponseId = null;
        if ($data['visibility'] === 'client' && !$ticket->isInternal()) {
            $plainText = $this->htmlToPlainText($htmlContent);
            $attachments = $this->extractInlineAttachments($htmlContent);
            $erpResult = $this->notifyErp($ticket, $ticket->board_column, $ticket->board_column, $plainText, $attachments, $htmlContent);

            if (!$erpResult) {
                return response()->json([
                    'success' => false,
                    'error'   => __('tickets.erp_sync_failed'),
                ], 500);
            }
            $erpResponseId = isset($erpResult['response_id']) ? $erpResult['response_id'] : null;
        }

        $comment = TicketComment::create([
            'ticket_id'       => $ticket->id,
            'author_id'       => Auth::id(),
            'author_name'     => Auth::user()->name,
            'visibility'      => $data['visibility'],
            'content'         => $htmlContent,
            'erp_response_id' => $erpResponseId,
        ]);

        // Parse @mentions from plain text content
        $plainForMentions = strip_tags($htmlContent);
        preg_match_all('/@(\w+(?:\.\w+)?)/', $plainForMentions, $matches);
        if (!empty($matches[1])) {
            $mentioned = TeamMember::where(function ($q) use ($matches) {
                foreach ($matches[1] as $handle) {
                    $q->orWhere('email', 'like', $handle . '@%')
                      ->orWhere('name', 'like', '%' . str_replace('.', ' ', $handle) . '%');
                }
            })->get();

            foreach ($mentioned as $member) {
                TicketMention::create([
                    'comment_id'          => $comment->id,
                    'mentioned_member_id' => $member->id,
                    'created_at'          => now(),
                ]);

                HubNotification::create([
                    'recipient_id' => $member->id,
                    'ticket_id'    => $ticket->id,
                    'type'         => 'mention',
                    'title'        => __('tickets.notif_mentioned', ['name' => Auth::user()->name, 'ticket' => $ticket->ticket_number]),
                    'message'      => Str::limit(strip_tags($htmlContent), 100),
                    'created_at'   => now(),
                ]);
            }
        }

        $comment->load(['author', 'mentions.mentionedMember']);

        return response()->json([
            'success' => true,
            'comment' => [
                'id'          => $comment->id,
                'author_id'   => $comment->author_id,
                'author_name' => $comment->author_name,
                'visibility'  => $comment->visibility,
                'source'      => $comment->source ?? 'dev',
                'content'     => $comment->content,
                'created_at'  => $comment->created_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Convert HTML content to readable plain text for ERP webhook.
     */
    private function htmlToPlainText($html)
    {
        $text = preg_replace('/<br\s*\/?>/', "\n", $html);
        $text = preg_replace('/<\/p>/', "\n", $text);
        $text = preg_replace('/<li>/', "- ", $text);
        $text = preg_replace('/<\/li>/', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Extract inline images and file links from HTML content,
     * read them from local storage, and return as base64 payloads
     * for the ERP webhook. Format: [{name, type, base64}]
     */
    private function extractInlineAttachments($html)
    {
        $attachments = [];
        $storageBase = 'ticket_attachments/';

        $dom = new \DOMDocument();
        @$dom->loadHTML(
            '<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Extract <img src="..."> (inline images from Summernote)
        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');
            $relativePath = $this->resolveStoragePath($src, $storageBase);
            if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                $filename = basename($relativePath);
                $attachments[] = [
                    'name'   => $filename,
                    'type'   => 'image',
                    'base64' => base64_encode(Storage::disk('public')->get($relativePath)),
                ];
            }
        }

        // Extract <a class="comment-file-link" href="..."> (non-image file attachments)
        foreach ($dom->getElementsByTagName('a') as $link) {
            $class = $link->getAttribute('class');
            if (strpos($class, 'comment-file-link') === false) {
                continue;
            }
            $href = $link->getAttribute('href');
            $relativePath = $this->resolveStoragePath($href, $storageBase);
            if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                $filename = basename($relativePath);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $type = in_array($ext, ['mp4', 'avi', 'mov', 'webm']) ? 'video' : 'document';
                $attachments[] = [
                    'name'   => $filename,
                    'type'   => $type,
                    'base64' => base64_encode(Storage::disk('public')->get($relativePath)),
                ];
            }
        }

        return $attachments;
    }

    /**
     * Convert a full asset URL to a relative storage path.
     * E.g. "http://hub.test/storage/ticket_attachments/8/abc.png" -> "ticket_attachments/8/abc.png"
     */
    private function resolveStoragePath($url, $prefix)
    {
        if (empty($url)) {
            return null;
        }

        // Try to extract path after /storage/
        $marker = '/storage/';
        $pos = strpos($url, $marker);
        if ($pos !== false) {
            $relative = substr($url, $pos + strlen($marker));
            if (strpos($relative, $prefix) === 0) {
                return $relative;
            }
        }

        return null;
    }

    /**
     * Sanitize HTML content: strip disallowed tags and remove dangerous attributes.
     * Prevents XSS from on* event handlers, javascript: URIs, etc.
     */
    private function sanitizeHtml($html)
    {
        if (empty($html)) {
            return '';
        }

        $allowedTags = '<p><br><strong><b><em><i><del><s><u><code><pre><ul><ol><li><a><img><span><div><blockquote><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><td><th><hr>';
        $html = strip_tags($html, $allowedTags);

        // Safe attributes per tag (everything else gets removed)
        $safeAttributes = [
            'a'     => ['href', 'target', 'rel', 'class', 'title'],
            'img'   => ['src', 'alt', 'width', 'height', 'class', 'loading'],
            'span'  => ['class'],
            'div'   => ['class'],
            'td'    => ['colspan', 'rowspan', 'class'],
            'th'    => ['colspan', 'rowspan', 'class'],
            'code'  => ['class'],
            'pre'   => ['class'],
            'ol'    => ['class', 'start'],
            'ul'    => ['class'],
            'li'    => ['class'],
            'table' => ['class'],
        ];

        $dom = new \DOMDocument();
        @$dom->loadHTML(
            '<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//*') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            $allowed = isset($safeAttributes[$tag]) ? $safeAttributes[$tag] : [];

            // Remove all attributes not in the whitelist for this tag
            $toRemove = [];
            foreach ($node->attributes as $attr) {
                if (!in_array($attr->name, $allowed)) {
                    $toRemove[] = $attr->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }

            // Sanitize href: block javascript: protocol
            if ($node->hasAttribute('href')) {
                $href = trim($node->getAttribute('href'));
                if (preg_match('/^\s*javascript\s*:/i', $href)) {
                    $node->setAttribute('href', '#');
                }
            }

            // Sanitize img src: only allow http(s) and data:image URIs
            if ($node->hasAttribute('src')) {
                $src = trim($node->getAttribute('src'));
                if (!preg_match('/^(https?:\/\/|data:image\/)/i', $src)) {
                    $node->removeAttribute('src');
                }
            }
        }

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $result = '';
        foreach ($wrapper->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    /**
     * Detect file type category from MIME type.
     */
    private function detectFileType($file)
    {
        $mime = $file->getMimeType() ?: '';

        if (strpos($mime, 'image/') === 0) {
            return 'image';
        }
        if (strpos($mime, 'video/') === 0) {
            return 'video';
        }

        return 'document';
    }

    /**
     * AJAX: edit a comment's content.
     */
    public function editComment(Request $request, Ticket $ticket, TicketComment $comment)
    {
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['success' => false], 403);
        }

        $data = $request->validate([
            'content' => 'required|string',
        ]);

        $sanitized = $this->sanitizeHtml($data['content']);

        // ERP-first: sync edit to ERP if this is a client-visible comment with erp_response_id
        if ($comment->visibility === 'client' && $comment->erp_response_id && !$ticket->isInternal()) {
            $erpOk = $this->sendErpAction($ticket, 'edit_comment', [
                'response_id'  => $comment->erp_response_id,
                'message'      => $this->htmlToPlainText($sanitized),
                'message_html' => $sanitized,
            ]);

            if (!$erpOk) {
                return response()->json([
                    'success' => false,
                    'error'   => __('tickets.erp_sync_failed'),
                ], 500);
            }
        }

        $comment->update(['content' => $sanitized]);

        return response()->json(['success' => true, 'content' => $comment->content]);
    }

    /**
     * AJAX: delete a comment (hard delete).
     */
    public function deleteComment(Request $request, Ticket $ticket, TicketComment $comment)
    {
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['success' => false], 403);
        }

        // ERP-first: sync delete to ERP if this is a client-visible comment with erp_response_id
        if ($comment->visibility === 'client' && $comment->erp_response_id && !$ticket->isInternal()) {
            $erpOk = $this->sendErpAction($ticket, 'delete_comment', [
                'response_id' => $comment->erp_response_id,
            ]);

            if (!$erpOk) {
                return response()->json([
                    'success' => false,
                    'error'   => __('tickets.erp_sync_failed'),
                ], 500);
            }
        }

        // Delete associated attachments from disk
        foreach ($comment->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
            $att->delete();
        }

        // Delete mentions
        $comment->mentions()->delete();

        $comment->delete();

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: upload a file for inline embedding in comments (Summernote).
     * Images are inserted as <img>, other files as download links.
     */
    public function uploadFile(Request $request, Ticket $ticket)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,zip,rar,mp4,mov,avi',
        ]);

        $file = $request->file('file');

        // Double-check extension as safety net
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'html', 'htm', 'svg', 'js', 'sh', 'bat', 'exe', 'htaccess'];
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        if (in_array($extension, $dangerousExtensions)) {
            return response()->json(['error' => __('tickets.file_type_not_allowed')], 422);
        }

        $filename = Str::random(20) . '.' . $extension;
        $dir = 'ticket_attachments/' . $ticket->id;

        Storage::disk('public')->put($dir . '/' . $filename, file_get_contents($file->getRealPath()));

        // Track in database for traceability
        TicketAttachment::create([
            'ticket_id'  => $ticket->id,
            'comment_id' => null,
            'file_name'  => $file->getClientOriginalName(),
            'file_path'  => $dir . '/' . $filename,
            'file_type'  => $this->detectFileType($file),
            'file_size'  => $file->getSize(),
            'source'     => 'dev',
            'created_at' => now(),
        ]);

        return response()->json([
            'url' => asset('storage/' . $dir . '/' . $filename),
        ]);
    }

    /**
     * AJAX: assign ticket to a team member.
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => 'nullable|exists:team_members,id',
        ]);

        $oldAssignee = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $data['assigned_to']]);

        if ($data['assigned_to'] && $data['assigned_to'] != $oldAssignee) {
            HubNotification::create([
                'recipient_id' => $data['assigned_to'],
                'ticket_id'    => $ticket->id,
                'type'         => 'assignment',
                'title'        => __('tickets.notif_assigned', ['ticket' => $ticket->ticket_number]),
                'message'      => __('tickets.notif_assigned_by', ['name' => Auth::user()->name]),
                'created_at'   => now(),
            ]);

            $assignee = TeamMember::find($data['assigned_to']);

            TicketStatusHistory::create([
                'ticket_id'  => $ticket->id,
                'old_column' => $ticket->board_column,
                'new_column' => $ticket->board_column,
                'changed_by' => Auth::id(),
                'notes'      => __('tickets.assigned_to_note', ['name' => $assignee->name]),
                'created_at' => now(),
            ]);

            $ticket->load('client');
            SlackNotifier::ticketAssigned($ticket, $assignee->name, Auth::user()->name);
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: update dev fields (pr_link, commit_info, sp_notes, deploy_notes).
     */
    public function updateDevFields(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'pr_link'      => 'nullable|string|max:500',
            'commit_info'  => 'nullable|string|max:500',
            'sp_notes'     => 'nullable|string',
            'deploy_notes' => 'nullable|string',
            'due_date'     => 'nullable|date',
        ]);

        $ticket->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: update subject and description (internal tickets only).
     */
    public function updateContent(Request $request, Ticket $ticket)
    {
        if (!$ticket->isInternal()) {
            return response()->json(['success' => false, 'error' => 'Only internal tickets can be edited.'], 403);
        }

        $data = $request->validate([
            'subject'     => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        $ticket->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: add a PR link to a ticket.
     */
    public function addPrLink(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'url'   => 'required|url|max:500',
            'title' => 'nullable|string|max:255',
        ]);

        $link = $ticket->prLinks()->create([
            'url'   => $data['url'],
            'title' => $data['title'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'link'    => [
                'id'    => $link->id,
                'url'   => $link->url,
                'title' => $link->title,
            ],
        ]);
    }

    /**
     * AJAX: remove a PR link from a ticket.
     */
    public function removePrLink(Ticket $ticket, $linkId)
    {
        $ticket->prLinks()->where('id', $linkId)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: add a commit to a ticket.
     */
    public function addCommit(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'hash'    => 'required|string|max:40',
            'message' => 'nullable|string|max:255',
            'url'     => 'nullable|url|max:500',
        ]);

        $commit = $ticket->commits()->create($data);

        return response()->json([
            'success' => true,
            'commit'  => [
                'id'      => $commit->id,
                'hash'    => $commit->hash,
                'message' => $commit->message,
                'url'     => $commit->url,
            ],
        ]);
    }

    /**
     * AJAX: remove a commit from a ticket.
     */
    public function removeCommit(Ticket $ticket, $commitId)
    {
        $ticket->commits()->where('id', $commitId)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: upload .sql file for SP notes.
     */
    public function uploadSpFile(Request $request, Ticket $ticket)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:sql,txt',
        ]);

        $file = $request->file('file');
        $dir = 'attachments/tickets/' . $ticket->id . '/sp';
        $path = $file->store($dir, 'public');

        $att = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => 'document',
            'file_size' => $file->getSize(),
            'source'    => 'sp_file',
        ]);

        return response()->json([
            'success' => true,
            'file'    => [
                'id'   => $att->id,
                'name' => $att->file_name,
                'url'  => Storage::disk('public')->url($path),
                'size' => $att->file_size,
            ],
        ]);
    }

    /**
     * AJAX: remove SP file attachment.
     */
    public function removeSpFile(Ticket $ticket, $fileId)
    {
        $att = $ticket->attachments()->where('id', $fileId)->where('source', 'sp_file')->first();
        if ($att) {
            Storage::disk('public')->delete($att->file_path);
            $att->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: list all labels (for dropdown selector).
     */
    public function labels()
    {
        $labels = \App\Models\Label::orderBy('name')->get(['id', 'name', 'color']);

        return response()->json($labels);
    }

    /**
     * AJAX: create a new label.
     */
    public function createLabel(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50|unique:labels,name',
            'color' => 'required|string|max:7',
        ]);

        $label = \App\Models\Label::create($data);

        return response()->json([
            'success' => true,
            'label'   => ['id' => $label->id, 'name' => $label->name, 'color' => $label->color],
        ]);
    }

    /**
     * AJAX: delete a label.
     */
    public function deleteLabel(\App\Models\Label $label)
    {
        $label->delete();

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: sync labels for a ticket (replace all with given IDs).
     */
    public function syncLabels(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'label_ids'   => 'nullable|array',
            'label_ids.*' => 'exists:labels,id',
        ]);

        $ticket->labels()->sync($data['label_ids'] ?? []);

        $labels = $ticket->labels()->get(['labels.id', 'name', 'color']);

        return response()->json([
            'success' => true,
            'labels'  => $labels,
        ]);
    }

    /**
     * Send a specific action to the client's ERP webhook.
     */
    private function sendErpAction(Ticket $ticket, string $action, array $extra = [])
    {
        $client = $ticket->client;
        if (!$client || !$client->api_callback_url) {
            return true;
        }

        try {
            $http = new \GuzzleHttp\Client(['timeout' => 15]);
            $payload = array_merge([
                'hub_ticket_id'   => $ticket->id,
                'local_ticket_id' => $ticket->external_ticket_id,
                'action'          => $action,
            ], $extra);

            $erpWebhookUrl = rtrim($client->api_callback_url, '/') . '/api/support/webhook';
            $response = $http->post($erpWebhookUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $client->api_key,
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return !empty($body['success']);
        } catch (\Exception $e) {
            Log::warning('ERP action "' . $action . '" failed for ticket ' . $ticket->ticket_number . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send status update webhook to the client's ERP.
     * Returns array with response body on success, false on failure.
     */
    private function notifyErp(Ticket $ticket, $oldColumn, $newColumn, $message = null, array $attachments = [], $messageHtml = null)
    {
        $client = $ticket->client;

        if (!$client->api_callback_url) {
            return true;
        }

        // Map hub columns to ERP statuses
        $statusMap = [
            'to_do'             => 'sent',
            'in_progress'       => 'in_progress',
            'blocked'           => 'blocked',
            'code_review'       => 'in_progress',
            'qa_testing'        => 'testing',
            'ready_for_release' => 'testing',
            'done'              => 'resolved',
        ];

        // Use explicit message if provided, otherwise use resolution_message for done
        $clientMessage = $message;
        if ($clientMessage === null && $newColumn === 'done') {
            $clientMessage = $ticket->resolution_message;
        }

        $erpStatus = isset($statusMap[$newColumn]) ? $statusMap[$newColumn] : $newColumn;

        try {
            $http = new \GuzzleHttp\Client(['timeout' => 15]);
            $payload = [
                'hub_ticket_id'     => $ticket->id,
                'local_ticket_id'   => $ticket->external_ticket_id,
                'new_status'        => $erpStatus,
                'assigned_to'       => 'Equipo de Soporte',
                'message_to_client' => $clientMessage,
                'attachments'       => $attachments,
            ];
            if ($messageHtml !== null) {
                $payload['message_html'] = $messageHtml;
            }
            $erpWebhookUrl = rtrim($client->api_callback_url, '/') . '/api/support/webhook';
            $response = $http->post($erpWebhookUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $client->api_key,
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!empty($body['success'])) {
                return $body; // Return full body so caller can access response_id etc.
            }
            return false;
        } catch (\Exception $e) {
            Log::warning('ERP webhook failed for ticket ' . $ticket->ticket_number . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * AJAX: manually override the AI incident classification.
     */
    public function classifyOverride(Request $request, Ticket $ticket)
    {
        $validTypes = implode(',', array_keys(config('support.incident_types')));
        $request->validate([
            'incident_type' => 'required|in:' . $validTypes,
        ]);

        $incidentTypes  = config('support.incident_types');
        $incidentWeight = $incidentTypes[$request->incident_type]['weight'];
        $clientWeights  = config('support.client_priority_weights');
        $clientWeight   = $clientWeights[$ticket->client_priority] ?? 3;

        $ticket->update([
            'incident_type'      => $request->incident_type,
            'incident_weight'    => $incidentWeight,
            'effective_priority' => $clientWeight * $incidentWeight,
            'ai_status'          => 'manual',
        ]);

        return response()->json([
            'success'            => true,
            'incident_type'      => $request->incident_type,
            'incident_label'     => __('reports.incident_' . $request->incident_type),
            'effective_priority' => $clientWeight * $incidentWeight,
        ]);
    }

    /**
     * AJAX: archive a ticket.
     */
    public function archive(Ticket $ticket)
    {
        if ($ticket->board_column !== 'done') {
            return response()->json(['success' => false, 'error' => __('tickets.archive_requires_done')], 422);
        }

        $ticket->update(['archived_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: soft-delete an archived ticket.
     */
    public function destroy(Ticket $ticket)
    {
        if (!$ticket->archived_at) {
            return response()->json(['success' => false, 'error' => 'Ticket must be archived first.'], 422);
        }

        $this->notifyErpDeleted($ticket);

        $ticket->delete();

        return response()->json([
            'success'  => true,
            'redirect' => route('tickets.archived'),
        ]);
    }

    /**
     * Notify ERP that a ticket has been deleted so it can soft-delete its local copy.
     */
    private function notifyErpDeleted(Ticket $ticket)
    {
        $client = $ticket->client;

        if (!$client || !$client->api_callback_url) {
            return;
        }

        try {
            $http = new \GuzzleHttp\Client(['timeout' => 10]);
            $erpWebhookUrl = rtrim($client->api_callback_url, '/') . '/api/support/webhook';
            $http->post($erpWebhookUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $client->api_key,
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'hub_ticket_id'   => $ticket->id,
                    'local_ticket_id' => $ticket->external_ticket_id,
                    'action'          => 'deleted',
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('ERP webhook (delete) failed for ticket ' . $ticket->ticket_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Archived tickets view.
     */
    public function archived()
    {
        $tickets = Ticket::with(['client', 'assignee'])
            ->archived()
            ->orderBy('archived_at', 'desc')
            ->paginate(25);

        return view('tickets.archived', compact('tickets'));
    }

    /**
     * AJAX: return unread notification count for polling.
     */
    public function unreadNotificationCount()
    {
        $count = Auth::user()->unreadNotificationsCount();

        return response()->json(['count' => $count]);
    }

    /**
     * AJAX: mark a single notification as read and return its ticket URL.
     */
    public function markNotificationRead($id)
    {
        $notification = HubNotification::where('id', $id)
            ->where('recipient_id', Auth::id())
            ->firstOrFail();

        $notification->markAsRead();

        $url = $notification->ticket_id ? route('tickets.show', $notification->ticket_id) : null;

        return response()->json(['success' => true, 'url' => $url]);
    }

    /**
     * AJAX: mark all notifications as read for current user.
     */
    public function markAllNotificationsRead()
    {
        HubNotification::where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function initials($name)
    {
        $parts = explode(' ', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }

    private function timeInColumn(Ticket $ticket)
    {
        $lastMove = $ticket->statusHistory()
            ->where('new_column', $ticket->board_column)
            ->latest('created_at')
            ->first();

        if (!$lastMove || !$lastMove->created_at) {
            return $ticket->created_at->diffForHumans(null, true);
        }

        return $lastMove->created_at->diffForHumans(null, true);
    }
}
