<div class="comment-item {{ $comment->visibility }}{{ $comment->source === 'client' ? ' source-client' : '' }} author-color-{{ ($comment->author_id ?? 0) % 8 }}" data-comment-id="{{ $comment->id }}">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            @if($comment->source === 'client')
                <span class="badge badge-client-source mr-1"><i class="fas fa-user mr-1"></i>{{ __('tickets.client_label') }}</span>
            @endif
            <strong class="small">{{ $comment->author_name }}</strong>
        </div>
        <div class="d-flex align-items-center">
            <small class="text-muted mr-2">{{ $comment->created_at->format('M d, Y H:i') }}</small>
            @if($comment->source !== 'client')
                <div class="comment-actions">
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 mr-1 btn-edit-comment" data-id="{{ $comment->id }}" title="Editar"><i class="fas fa-pencil-alt" style="font-size:0.7rem;"></i></button>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-delete-comment" data-id="{{ $comment->id }}" title="Eliminar"><i class="fas fa-trash" style="font-size:0.7rem;"></i></button>
                </div>
            @endif
        </div>
    </div>
    <div class="comment-body">
        @if($comment->content && preg_match('/<[a-z][\s\S]*>/i', $comment->content))
            {!! \App\Models\TicketComment::sanitizeForDisplay($comment->content) !!}
        @else
            {!! \App\Models\TicketComment::formatContent($comment->content) !!}
        @endif
    </div>
    @if($comment->mentions->isNotEmpty())
        <div class="small text-info mt-1">
            <i class="fas fa-at"></i>
            {{ $comment->mentions->map(function($m){ return $m->mentionedMember->name; })->implode(', ') }}
        </div>
    @endif
    @if($comment->attachments->isNotEmpty())
        <div class="comment-attachments mt-2">
            @foreach($comment->attachments as $att)
                <div class="comment-attachment-item d-inline-block mr-2 mb-1">
                    @if($att->isImage())
                        <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}" class="comment-att-thumb">
                            <img src="{{ asset('storage/' . $att->file_path) }}" alt="{{ $att->file_name }}">
                        </a>
                    @elseif($att->isVideo())
                        <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}" class="comment-att-file">
                            <i class="fas fa-play-circle text-danger mr-1"></i>{{ Str::limit($att->file_name, 20) }}
                        </a>
                    @else
                        <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}" class="comment-att-file">
                            <i class="fas fa-paperclip mr-1"></i>{{ Str::limit($att->file_name, 20) }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
