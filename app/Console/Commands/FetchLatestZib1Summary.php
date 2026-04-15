<?php

namespace App\Console\Commands;

use App\Services\GenerateLatestSummaryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:fetch-latest-zib1-summary')]
#[Description('Command description')]
class FetchLatestZib1Summary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GenerateLatestSummaryService $generateLatestSummaryService): int
    {
        try {
            $summary = $generateLatestSummaryService->run();
            $this->info("Zusammenfassung gespeichert: {$summary->source_title}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Fehler beim Verarbeiten der letzten ZIB1 Folge.');

            return self::FAILURE;
        }
    }
}
