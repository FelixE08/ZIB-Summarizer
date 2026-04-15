<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiSummaryQuestionService
{
    private const int MAX_429_RETRIES_PER_MODEL = 4;
    private const int REQUEST_TIMEOUT_SECONDS = 15;
    private const int CONNECT_TIMEOUT_SECONDS = 8;
    private const int MAX_OUTPUT_TOKENS = 900;

    public function answer(string $summary, string $question): string
    {
        $apiKey = (string) config('services.ai.api_key');
        $baseUrl = rtrim((string) config('services.ai.base_url'), '/');
        $primaryModel = (string) config('services.ai.model', 'gemini-2.0-flash');
        $fallbackModel = (string) config('services.ai.fallback_model', 'gemini-2.0-flash-lite');

        if ($apiKey === '') {
            throw new RuntimeException('AI_API_KEY fehlt in der .env Datei.');
        }

        $basePayload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein hilfreicher Nachrichten-Assistent. Beantworte Fragen praezise und sachlich. Nutze die vorhandene Zusammenfassung als Kontext, darfst aber auch allgemeines Wissen einbeziehen, wenn es der Frage hilft. Wenn etwas unsicher ist, weise kurz darauf hin.',
                ],
                [
                    'role' => 'user',
                    'content' => "ZUSAMMENFASSUNG:\n{$summary}\n\nFRAGE:\n{$question}",
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
        ];

        $modelsToTry = array_values(array_unique(array_filter([
            $primaryModel,
            $fallbackModel,
        ])));

        $response = null;
        foreach ($modelsToTry as $candidateModel) {
            $payload = $basePayload;
            $payload['model'] = $candidateModel;

            for ($attempt = 1; $attempt <= self::MAX_429_RETRIES_PER_MODEL; $attempt++) {
                try {
                    $response = Http::withToken($apiKey)
                        ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                        ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                        ->post("{$baseUrl}/chat/completions", $payload);
                } catch (ConnectionException) {
                    $response = null;
                }

                if ($response === null) {
                    if ($attempt < self::MAX_429_RETRIES_PER_MODEL) {
                        usleep($attempt * 500000);
                    }
                    continue;
                }

                if ($response->status() !== 429) {
                    break;
                }

                if ($attempt < self::MAX_429_RETRIES_PER_MODEL) {
                    $this->sleepForBackoffAttempt($attempt, $candidateModel);
                }
            }

            if ($response !== null && $response->successful()) {
                break;
            }

            if ($response !== null && $response->status() === 429) {
                Log::warning('QA request received 429 and will try next model.', [
                    'model' => $candidateModel,
                    'fallback_model' => $fallbackModel,
                ]);
            }
        }

        if ($response === null) {
            throw new RuntimeException('Die KI ist aktuell nicht erreichbar. Bitte versuche es gleich erneut.');
        }

        if (! $response->successful()) {
            if ($response->status() === 429) {
                throw new RuntimeException('Die KI ist derzeit stark ausgelastet. Bitte versuche es in 1-2 Minuten erneut.');
            }

            throw new RuntimeException('Die Antwort zur Frage konnte nicht erstellt werden (Status '.$response->status().').');
        }

        $answer = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($answer === '') {
            throw new RuntimeException('Die KI hat keine Antwort geliefert. Bitte formuliere die Frage anders.');
        }

        return $answer;
    }

    private function sleepForBackoffAttempt(int $attempt, string $model): void
    {
        $baseDelayMs = 1000;
        $delayMs = min($baseDelayMs * (2 ** ($attempt - 1)), 8000);
        Log::warning('QA request hit rate limit (429). Retrying with exponential backoff.', [
            'attempt' => $attempt,
            'sleep_ms' => $delayMs,
            'model' => $model,
        ]);

        usleep($delayMs * 1000);
    }
}
