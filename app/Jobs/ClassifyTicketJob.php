<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\GeminiClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClassifyTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 2;
    public $timeout = 20;

    private $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function handle(GeminiClassifier $classifier)
    {
        $result = $classifier->classify(
            $this->ticket->subject,
            $this->ticket->description ?? '',
            $this->ticket->module ?? ''
        );

        if (! $result) {
            $this->applyFallback();
            return;
        }

        $incidentTypes  = config('support.incident_types');
        $incidentWeight = $incidentTypes[$result['incident_type']]['weight']
            ?? config('support.ai_fallback_weight', 3);

        $clientWeights = config('support.client_priority_weights');
        $clientWeight  = $clientWeights[$this->ticket->client_priority] ?? 3;

        $this->ticket->update([
            'incident_type'    => $result['incident_type'],
            'incident_weight'  => $incidentWeight,
            'effective_priority' => $clientWeight * $incidentWeight,
            'ai_status'        => 'classified',
            'ai_suggestion'    => $result['ai_suggestion'],
            'ai_justification' => $result['ai_justification'],
        ]);

        Log::info("ClassifyTicketJob: ticket #{$this->ticket->id} ({$this->ticket->ticket_number}) classified as [{$result['incident_type']}]");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ClassifyTicketJob: failed for ticket #{$this->ticket->id}", [
            'error' => $e->getMessage(),
        ]);
        $this->applyFallback();
    }

    private function applyFallback(): void
    {
        $this->ticket->update([
            'incident_type'  => config('support.ai_fallback_incident_type'),
            'incident_weight' => config('support.ai_fallback_weight', 3),
            'ai_status'      => 'failed',
        ]);
    }
}
