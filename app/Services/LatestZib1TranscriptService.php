<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LatestZib1TranscriptService
{
    public function fetchLatest(): array
    {
        $seriesUrl = 'https://on.orf.at/sendereihe/1203/zib-1';
        $seriesResponse = $this->http()->get($seriesUrl);

        if (! $seriesResponse->successful()) {
            throw new RuntimeException('Konnte die ORF ZIB1 Sendungsseite nicht laden.');
        }

        preg_match('/https:\/\/on\.orf\.at\/video\/\d+\/[^"\s<]+/i', $seriesResponse->body(), $videoMatch);
        $videoUrl = $videoMatch[0] ?? null;
        if (! $videoUrl) {
            throw new RuntimeException('Kein ZIB1 Video auf ORF ON gefunden.');
        }

        $videoPageResponse = $this->http()->get($videoUrl);
        if (! $videoPageResponse->successful()) {
            throw new RuntimeException('Konnte die ORF Videoseite nicht laden.');
        }

        $videoHtml = $videoPageResponse->body();
        preg_match('/baseUrl:"([^"]+)",user:"([^"]+)",password:"([^"]+)"/', $videoHtml, $apiMatch);
        $apiBaseUrl = $apiMatch[1] ?? null;
        $apiUser = $apiMatch[2] ?? null;
        $apiPassword = $apiMatch[3] ?? null;

        if (! $apiBaseUrl || ! $apiUser || ! $apiPassword) {
            throw new RuntimeException('ORF API Konfiguration konnte nicht aus der Seite gelesen werden.');
        }

        preg_match_all('/subtitle\/(\d+)/', $videoHtml, $subtitleMatches);
        $subtitleIds = collect($subtitleMatches[1] ?? [])->unique()->values();
        if ($subtitleIds->isEmpty()) {
            throw new RuntimeException('Keine Untertitel-IDs fuer diese Folge gefunden.');
        }

        $parts = [];
        foreach ($subtitleIds as $subtitleId) {
            $subtitleResponse = $this->http()
                ->withBasicAuth($apiUser, $apiPassword)
                ->get(rtrim($apiBaseUrl, '/')."/subtitle/{$subtitleId}");

            if (! $subtitleResponse->successful()) {
                continue;
            }

            $vttUrl = (string) data_get($subtitleResponse->json(), 'vtt_url', '');
            if ($vttUrl === '') {
                continue;
            }

            $vttResponse = $this->http()->get($vttUrl);
            if (! $vttResponse->successful() || trim($vttResponse->body()) === '') {
                continue;
            }

            $parts[] = $this->parseVtt($vttResponse->body());
        }

        $transcript = trim(implode(' ', array_filter($parts)));
        if ($transcript === '') {
            throw new RuntimeException('Untertitel konnten von ORF ON nicht geladen werden.');
        }

        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $videoHtml, $titleMatch);
        $title = trim(strip_tags(html_entity_decode($titleMatch[1] ?? 'ZIB 1', ENT_QUOTES | ENT_HTML5)));

        return [
            'title' => $title,
            'source_url' => $videoUrl,
            'published_at' => Carbon::now(),
            'transcript' => $transcript,
        ];
    }

    private function parseVtt(string $payload): string
    {
        $lines = preg_split('/\R/', $payload) ?: [];

        $text = collect($lines)
            ->map(fn ($line) => trim($line))
            ->reject(fn ($line) => $line === '' || str_starts_with($line, 'WEBVTT'))
            ->reject(fn ($line) => preg_match('/^\d+$/', $line) === 1)
            ->reject(fn ($line) => str_contains($line, '-->'))
            ->reject(fn ($line) => $line === '*')
            ->map(fn ($line) => html_entity_decode($line, ENT_QUOTES | ENT_HTML5))
            ->implode(' ');

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function http(): PendingRequest
    {
        return Http::timeout(20)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
            'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
        ]);
    }
}
