<?php

namespace App\Services;

use App\Models\EpisodeSummary;

class GenerateLatestSummaryService
{
    public function __construct(
        private readonly LatestZib1TranscriptService $transcriptService,
        private readonly AiSummaryService $aiSummaryService,
    ) {}

    public function run(): EpisodeSummary
    {
        // Fetching transcript + AI summary can exceed default 30s in web requests.
        if (function_exists('set_time_limit')) {
            set_time_limit(180);
        }

        $latest = $this->transcriptService->fetchLatest();

        $existing = EpisodeSummary::where('source_url', $latest['source_url'])->first();

        $existingSummary = (string) ($existing?->summary ?? '');
        $isFallbackSummary = str_contains($existingSummary, 'Automatische Notfall-Zusammenfassung');

        if ($existing && mb_strlen($existingSummary) >= 1800 && ! $isFallbackSummary) {
            return $existing;
        }

        $summary = $this->aiSummaryService->summarize($latest['transcript']);

        if ($existing) {
            $existing->update([
                'source_title' => $latest['title'],
                'published_at' => $latest['published_at'],
                'transcript' => $latest['transcript'],
                'summary' => $summary,
            ]);

            return $existing->fresh();
        }

        return EpisodeSummary::create([
            'source_title' => $latest['title'],
            'source_url' => $latest['source_url'],
            'published_at' => $latest['published_at'],
            'transcript' => $latest['transcript'],
            'summary' => $summary,
        ]);
    }
}
