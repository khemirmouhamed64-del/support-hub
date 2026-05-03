<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClassifier
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    /**
     * Classify a ticket and return [incident_type, ai_suggestion, ai_justification].
     * Returns null if the API fails or returns invalid output.
     */
    public function classify(string $subject, string $description, string $module = ''): ?array
    {
        $prompt = $this->buildPrompt($subject, $description, $module);
        Log::debug('GeminiClassifier: sending request', [
            'subject' => $subject,
            'module'  => $module,
            'prompt'  => $prompt,
        ]);

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.1,
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('GeminiClassifier: API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $raw = $response->json('candidates.0.content.parts.0.text');
            Log::debug('GeminiClassifier: raw response', ['raw' => $raw]);
            $data = json_decode($raw, true);

            if (! $data || ! isset($data['incident_type'])) {
                Log::warning('GeminiClassifier: Invalid or missing incident_type in response', ['raw' => $raw]);
                return null;
            }

            $validTypes = array_keys(config('support.incident_types', []));
            if (! in_array($data['incident_type'], $validTypes)) {
                Log::warning('GeminiClassifier: Unknown incident_type returned', ['type' => $data['incident_type']]);
                return null;
            }

            return [
                'incident_type'    => $data['incident_type'],
                'ai_suggestion'    => $data['dev_suggestion'] ?? null,
                'ai_justification' => $data['short_justification'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('GeminiClassifier: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(string $subject, string $description, string $module): string
    {
        $moduleHint = $module ? "\nMódulo reportado: {$module}" : '';
        return "Asunto: {$subject}{$moduleHint}\n\nDescripción: {$description}";
    }

    private function systemPrompt(): string
    {
        $modules = implode("\n", array_map(
            fn($m) => "- {$m['label']}",
            config('support.modules', [])
        ));

        return <<<PROMPT
You are a technical support analyst expert in ERP and business management software for SMBs.

System modules:
{$modules}

Classify the ticket into EXACTLY ONE of these types:
- operacion_bloqueada: client cannot invoice, sell, collect or register payments
- funcionalidad_critica: error in a key module but a workaround exists or it does not fully block operations
- funcionalidad_menor: visual or cosmetic error, or issue in a non-essential feature
- configuracion: needs help with setup, permissions, users, or initial configuration
- consulta: usage question, general doubt, or how-to inquiry

Respond ONLY with this JSON, no additional text:
{
  "incident_type": "...",
  "dev_suggestion": "...",
  "short_justification": "..."
}

dev_suggestion: brief technical recommendation for the developer (max 15 words).
short_justification: reason for the classification (max 20 words).
PROMPT;
    }
}
