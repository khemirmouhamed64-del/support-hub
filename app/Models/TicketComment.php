<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    protected $fillable = [
        'ticket_id',
        'author_id',
        'author_name',
        'visibility',
        'source',
        'content',
        'erp_response_id',
    ];

    // --- Relationships ---

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author()
    {
        return $this->belongsTo(TeamMember::class, 'author_id');
    }

    public function mentions()
    {
        return $this->hasMany(TicketMention::class, 'comment_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'comment_id');
    }

    // --- Scopes ---

    public function scopeInternal($query)
    {
        return $query->where('visibility', 'internal');
    }

    public function scopeForClient($query)
    {
        return $query->where('visibility', 'client');
    }

    // --- Helpers ---

    /**
     * Sanitize HTML for safe display. Removes dangerous attributes (on*, javascript:, style).
     * Used as defense-in-depth for content already sanitized on input.
     */
    public static function sanitizeForDisplay($html)
    {
        if (empty($html)) {
            return '';
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML(
            '<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);

        // Remove all on* event attributes and style attributes
        foreach ($xpath->query('//*') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $toRemove = [];
            foreach ($node->attributes as $attr) {
                if (strpos($attr->name, 'on') === 0 || $attr->name === 'style') {
                    $toRemove[] = $attr->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }

            if ($node->hasAttribute('href')) {
                $href = trim($node->getAttribute('href'));
                if (preg_match('/^\s*javascript\s*:/i', $href)) {
                    $node->setAttribute('href', '#');
                }
            }

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
     * Render plain text content as formatted HTML with markdown-like syntax.
     * Supports: **bold**, *italic*, ~~strikethrough~~, `code`, ```code blocks```,
     * [text](url) links, auto-linked URLs, bullet lists (- item), numbered lists (1. item).
     */
    public static function formatContent($text)
    {
        if (!$text) {
            return '';
        }

        $text = e($text);

        // Code blocks: ```...``` (must be first to protect content inside)
        $codeBlocks = [];
        $text = preg_replace_callback('/```([\s\S]*?)```/', function ($m) use (&$codeBlocks) {
            $placeholder = '%%CODEBLOCK_' . count($codeBlocks) . '%%';
            $codeBlocks[$placeholder] = '<pre class="comment-code-block"><code>' . $m[1] . '</code></pre>';
            return $placeholder;
        }, $text);

        // Inline code: `...` (protect from further processing)
        $inlineCodes = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$inlineCodes) {
            $placeholder = '%%INLINECODE_' . count($inlineCodes) . '%%';
            $inlineCodes[$placeholder] = '<code class="comment-inline-code">' . $m[1] . '</code>';
            return $placeholder;
        }, $text);

        // Bold: **text**
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Italic: *text*
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Strikethrough: ~~text~~
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);

        // Markdown links: [text](url) — before auto-links
        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);

        // Auto-link URLs not already inside an <a> tag
        $text = preg_replace('/(?<!href="|">)(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);

        // Process lists line by line
        $lines = explode("\n", $text);
        $result = [];
        $inUl = false;
        $inOl = false;

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            // Bullet list: - item
            if (preg_match('/^[\-\*] (.+)$/', $trimmed, $m)) {
                if ($inOl) { $result[] = '</ol>'; $inOl = false; }
                if (!$inUl) { $result[] = '<ul class="comment-list">'; $inUl = true; }
                $result[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Numbered list: 1. item
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                if ($inUl) { $result[] = '</ul>'; $inUl = false; }
                if (!$inOl) { $result[] = '<ol class="comment-list">'; $inOl = true; }
                $result[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Not a list line — close any open lists
            if ($inUl) { $result[] = '</ul>'; $inUl = false; }
            if ($inOl) { $result[] = '</ol>'; $inOl = false; }
            $result[] = $line;
        }

        if ($inUl) { $result[] = '</ul>'; }
        if ($inOl) { $result[] = '</ol>'; }

        $text = implode("\n", $result);

        // Restore code blocks and inline code
        foreach ($codeBlocks as $placeholder => $html) {
            $text = str_replace($placeholder, $html, $text);
        }
        foreach ($inlineCodes as $placeholder => $html) {
            $text = str_replace($placeholder, $html, $text);
        }

        // Newlines to <br> (but not inside <pre>, <ul>, <ol> blocks)
        $parts = preg_split('/(<(?:pre|ul|ol)[\s\S]*?<\/(?:pre|ul|ol)>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if (!preg_match('/^<(?:pre|ul|ol)/', $part)) {
                $parts[$i] = nl2br($part);
            }
        }
        $text = implode('', $parts);

        return $text;
    }
}
