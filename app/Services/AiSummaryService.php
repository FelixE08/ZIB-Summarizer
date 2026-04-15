<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiSummaryService
{
    private const int MAX_429_RETRIES_PER_MODEL = 4;
    private const int MAX_5XX_RETRIES_PER_MODEL = 3;
    private const int FALLBACK_MAX_BULLETS = 10;
    private const int REQUEST_TIMEOUT_SECONDS = 15;
    private const int CONNECT_TIMEOUT_SECONDS = 8;
    private const int MAX_OUTPUT_TOKENS = 3200;
    private const int MAX_CONTINUATION_REQUESTS = 5;

    public function summarize(string $transcript): string
    {
        $apiKey = (string) config('services.ai.api_key');
        $baseUrl = rtrim((string) config('services.ai.base_url'), '/');
        $model = $this->resolvePreferredModel((string) config('services.ai.model'));
        $fallbackModel = (string) config('services.ai.fallback_model', '');
        $reasoningEffort = (string) config('services.ai.reasoning_effort', '');

        if ($apiKey === '') {
            throw new RuntimeException('AI_API_KEY fehlt in der .env Datei.');
        }

        $basePayload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Nachrichten-Analyst. Erstelle eine strukturierte, moeglichst vollstaendige Zusammenfassung und decke alle relevanten Themenbloecke der Sendung ab. Antworte direkt und wiederhole niemals das Eingabematerial.',
                ],
                [
                    'role' => 'user',
                    'content' => "Erstelle eine strukturierte Zusammenfassung des folgenden ZIB1-Transkripts.\n\n### TRANSKRIPT START ###\n{$transcript}\n### TRANSKRIPT ENDE ###",
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => self::MAX_OUTPUT_TOKENS,
        ];

        $modelsToTry = array_values(array_unique(array_filter([
            $model,
            $fallbackModel !== '' ? $fallbackModel : 'gemini-2.0-flash-lite',
        ])));

        $response = null;

        foreach ($modelsToTry as $candidateModel) {
            $payload = $basePayload;
            $payload['model'] = $candidateModel;
            $requestPayload = $payload;

            if ($reasoningEffort !== '' && $candidateModel === $model) {
                $payload['reasoning_effort'] = $reasoningEffort;
            } elseif ($candidateModel !== $model) {
                $payload['reasoning_effort'] = 'medium';
            }

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
                    if ($response->status() === 400) {
                        Log::warning('AI request returned 400. Retrying with compatibility payload.', [
                            'model' => $candidateModel,
                            'response' => $response->body(),
                        ]);

                        $compatibilityPayload = $this->buildCompatibilityPayload($payload);
                        $requestPayload = $compatibilityPayload;

                        try {
                            $response = Http::withToken($apiKey)
                                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                                ->post("{$baseUrl}/chat/completions", $compatibilityPayload);
                        } catch (ConnectionException) {
                            $response = null;
                        }
                    }

                    break;
                }

                if ($attempt < self::MAX_429_RETRIES_PER_MODEL) {
                    $this->sleepForBackoffAttempt($attempt, $candidateModel);
                }
            }

            if ($response === null) {
                continue;
            }

            if ($response->successful()) {
                break;
            }

            if ($response->status() !== 429) {
                if ($this->isRetriableServerError($response->status())) {
                    $response = $this->retryServerErrorRequest(
                        payload: $requestPayload,
                        apiKey: $apiKey,
                        baseUrl: $baseUrl,
                        model: $candidateModel,
                    );

                    if ($response === null) {
                        continue;
                    }

                    if ($response->successful()) {
                        break;
                    }
                }

                break;
            }

            Log::warning('AI request received 429 and will try next model.', [
                'model' => $candidateModel,
                'fallback_model' => $fallbackModel,
            ]);
        }

        if ($response === null) {
            Log::warning('AI request failed due to connection timeout. Falling back to local summary.');
            return $this->buildLocalFallbackSummary($transcript);
        }

        if (! $response->successful()) {
            if ($response->status() === 400) {
                Log::warning('AI request failed with 400. Falling back to local summary.', [
                    'model' => $model,
                    'fallback_model' => $fallbackModel,
                    'response' => $response->body(),
                ]);

                return $this->buildLocalFallbackSummary($transcript);
            }

            if ($response->status() === 429) {
                Log::warning('AI rate limit reached (429). Falling back to local summary.', [
                    'model' => $model,
                    'fallback_model' => $fallbackModel,
                ]);
                return $this->buildLocalFallbackSummary($transcript);
            }

            if ($this->isRetriableServerError($response->status())) {
                Log::warning('AI service unavailable (5xx). Falling back to local summary.', [
                    'status' => $response->status(),
                    'model' => $model,
                    'fallback_model' => $fallbackModel,
                ]);

                return $this->buildLocalFallbackSummary($transcript);
            }

            throw new RuntimeException('AI Zusammenfassung fehlgeschlagen: '.$response->status());
        }

        $summary = data_get($response->json(), 'choices.0.message.content', '');
        $finishReason = (string) data_get($response->json(), 'choices.0.finish_reason', '');

        if (! is_string($summary) || trim($summary) === '') {
            return $this->buildLocalFallbackSummary($transcript);
        }

        if ($finishReason === 'length'
            || $this->isLikelyTruncated(trim($summary))
            || $this->isLikelyIncompleteForTranscript($transcript, trim($summary))) {
            $summary = $this->extendTruncatedSummary(
                currentPayload: $basePayload,
                apiKey: $apiKey,
                baseUrl: $baseUrl,
                model: (string) data_get($response->json(), 'model', $model),
                currentSummary: trim($summary),
            );
        }

        return $this->formatSummary($summary);
    }

    private function buildLocalFallbackSummary(string $transcript): string
    {
        $lines = preg_split('/\R+/', $transcript) ?: [];
        $points = [];

        foreach ($lines as $line) {
            $clean = trim($line);

            if ($clean === '' || mb_strlen($clean) < 35) {
                continue;
            }

            $points[] = '- '.$clean;

            if (count($points) >= self::FALLBACK_MAX_BULLETS) {
                break;
            }
        }

        if ($points === []) {
            $excerpt = trim(mb_substr(preg_replace('/\s+/', ' ', $transcript) ?? '', 0, 900));
            $points[] = $excerpt !== '' ? '- '.$excerpt : '- Keine verwertbaren Transcript-Inhalte gefunden.';
        }

        return $this->formatSummary(implode("\n", [
            'Automatische Notfall-Zusammenfassung (AI temporär nicht verfügbar):',
            '',
            ...$points,
        ]));
    }

    private function resolvePreferredModel(string $configuredModel): string
    {
        if ($configuredModel === '') {
            return 'gemini-2.0-flash';
        }

        $lowerModel = strtolower($configuredModel);
        if (str_contains($lowerModel, 'gemini-1.')) {
            return 'gemini-2.0-flash';
        }

        return $configuredModel;
    }

    private function sleepForBackoffAttempt(int $attempt, string $model): void
    {
        $baseDelayMs = 1000;
        $delayMs = min($baseDelayMs * (2 ** ($attempt - 1)), 8000);
        Log::warning('AI rate limit (429). Retrying with exponential backoff.', [
            'attempt' => $attempt,
            'sleep_ms' => $delayMs,
            'model' => $model,
        ]);

        usleep($delayMs * 1000);
    }

    private function buildCompatibilityPayload(array $payload): array
    {
        unset($payload['safety_settings'], $payload['reasoning_effort'], $payload['max_output_tokens']);
        if (! array_key_exists('max_tokens', $payload)) {
            $payload['max_tokens'] = self::MAX_OUTPUT_TOKENS;
        }

        return $payload;
    }

    private function isRetriableServerError(int $status): bool
    {
        return in_array($status, [500, 502, 503, 504], true);
    }

    private function retryServerErrorRequest(
        array $payload,
        string $apiKey,
        string $baseUrl,
        string $model,
    ): ?\Illuminate\Http\Client\Response {
        for ($attempt = 1; $attempt <= self::MAX_5XX_RETRIES_PER_MODEL; $attempt++) {
            $delayMs = min(1000 * (2 ** ($attempt - 1)), 8000);
            Log::warning('AI server error (5xx). Retrying request.', [
                'attempt' => $attempt,
                'sleep_ms' => $delayMs,
                'model' => $model,
            ]);
            usleep($delayMs * 1000);

            try {
                $response = Http::withToken($apiKey)
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->post("{$baseUrl}/chat/completions", $payload);
            } catch (ConnectionException) {
                continue;
            }

            if (! $this->isRetriableServerError($response->status())) {
                return $response;
            }
        }

        return null;
    }

    private function extendTruncatedSummary(
        array $currentPayload,
        string $apiKey,
        string $baseUrl,
        string $model,
        string $currentSummary,
    ): string {
        $assembled = $currentSummary;
        $modelToUse = $this->resolvePreferredModel($model);

        for ($i = 1; $i <= self::MAX_CONTINUATION_REQUESTS; $i++) {
            $continuationPayload = $currentPayload;
            $continuationPayload['model'] = $modelToUse;
            $continuationPayload['messages'][] = [
                'role' => 'assistant',
                'content' => $assembled,
            ];
            $continuationPayload['messages'][] = [
                'role' => 'user',
                'content' => 'Setze die Zusammenfassung nahtlos fort. Keine Einleitung, keine Wiederholung, nur neue Punkte.',
            ];

            try {
                $continuationResponse = Http::withToken($apiKey)
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->post("{$baseUrl}/chat/completions", $continuationPayload);
            } catch (ConnectionException) {
                Log::warning('Summary continuation request failed due to connection timeout.', ['step' => $i]);
                break;
            }

            if (! $continuationResponse->successful()) {
                break;
            }

            $nextPart = trim((string) data_get($continuationResponse->json(), 'choices.0.message.content', ''));
            if ($nextPart === '') {
                break;
            }

            $assembled = trim($assembled."\n".$nextPart);
            $finishReason = (string) data_get($continuationResponse->json(), 'choices.0.finish_reason', '');

            if ($finishReason !== 'length') {
                break;
            }
        }

        return $assembled;
    }

    private function formatSummary(string $summary): string
    {
        $lines = preg_split('/\R+/', $summary) ?: [];
        $formatted = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($formatted !== [] && end($formatted) !== '') {
                    $formatted[] = '';
                }
                continue;
            }

            $line = preg_replace('/^\*+\s*/', '- ', $line) ?? $line;
            $line = preg_replace('/^[•·]\s*/u', '- ', $line) ?? $line;
            $line = preg_replace('/^\s*-\s*/', '- ', $line) ?? $line;

            if (preg_match('/^(I|II|III|IV|V|VI|VII|VIII|IX|X)\.\s+/i', $line) === 1) {
                $line = '## '.preg_replace('/^(I|II|III|IV|V|VI|VII|VIII|IX|X)\.\s+/i', '', $line);
            }

            $formatted[] = $line;
        }

        $formattedText = trim(implode("\n", $formatted));
        $formattedText = preg_replace("/\n{3,}/", "\n\n", $formattedText) ?? $formattedText;

        return $this->enforceStructuredLayout($formattedText);
    }

    private function enforceStructuredLayout(string $text): string
    {
        $text = preg_replace('/^Hier ist (eine|die)\s+strukturierte\s+Zusammenfassung[^:]*:\s*/iu', '', $text) ?? $text;
        $lines = preg_split('/\R+/', trim($text)) ?: [];

        $sections = [];
        $currentTitle = 'Kernaussagen';
        $currentBody = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\*+|\*+$/', '', $line) ?? $line;
            $line = trim($line);

            if (preg_match('/^-?\s*\d+\.\s+(.+)/u', $line, $matches) === 1) {
                if ($currentBody !== []) {
                    $sections[] = ['title' => $currentTitle, 'body' => implode(' ', $currentBody)];
                }
                $currentTitle = trim($matches[1]);
                $currentBody = [];
                continue;
            }

            if (str_starts_with($line, '## ')) {
                if ($currentBody !== []) {
                    $sections[] = ['title' => $currentTitle, 'body' => implode(' ', $currentBody)];
                }
                $currentTitle = trim(substr($line, 3));
                $currentBody = [];
                continue;
            }

            $cleanLine = preg_replace('/^-+\s*/', '', $line) ?? $line;
            $currentBody[] = $cleanLine;
        }

        if ($currentBody !== []) {
            $sections[] = ['title' => $currentTitle, 'body' => implode(' ', $currentBody)];
        }

        if ($sections === []) {
            $sections[] = ['title' => 'Kernaussagen', 'body' => trim($text)];
        }

        $out = ['# ZIB1 Zusammenfassung', ''];
        foreach ($sections as $section) {
            $title = trim($section['title']) !== '' ? trim($section['title']) : 'Thema';
            $body = trim($section['body']) !== '' ? trim($section['body']) : 'Keine Details verfügbar.';
            $out[] = '## '.$title;
            $out[] = $this->emphasizeImportantWords($body);
            $out[] = '';
        }

        return trim(implode("\n", $out));
    }

    private function isLikelyTruncated(string $summary): bool
    {
        if ($summary === '') {
            return true;
        }

        $lastChar = mb_substr($summary, -1);
        if (! in_array($lastChar, ['.', '!', '?', ':'], true)) {
            return true;
        }

        $lastWord = mb_substr($summary, max(0, mb_strlen($summary) - 12));
        if (preg_match('/\b[[:alpha:]]{1,2}$/u', $lastWord) === 1) {
            return true;
        }

        return false;
    }

    private function isLikelyIncompleteForTranscript(string $transcript, string $summary): bool
    {
        if (trim($summary) === '') {
            return true;
        }

        $transcriptLength = mb_strlen($transcript);
        $summaryLength = mb_strlen($summary);

        if ($transcriptLength < 8000) {
            return false;
        }

        return $summaryLength < 1800;
    }

    private function emphasizeImportantWords(string $text): string
    {
        $keywords = [
            'Ungarn', 'Österreich', 'Deutschland', 'Europa', 'EU',
            'Ukraine', 'Russland', 'NATO', 'Regierung', 'Parlament',
            'Wahl', 'Wahlen', 'Wirtschaft', 'Inflation', 'Budget',
            'Energie', 'Klima', 'Migration', 'Justiz', 'Sicherheit',
        ];

        $pattern = '/(?<!\*)\b('.implode('|', $keywords).')\b(?!\*)/iu';

        return preg_replace($pattern, '**$1**', $text) ?? $text;
    }
}
