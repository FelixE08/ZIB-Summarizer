<?php

namespace App\Http\Controllers;

use App\Models\EpisodeSummary;
use App\Services\AiSummaryQuestionService;
use App\Services\GenerateLatestSummaryService;
use App\Services\MarketPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SummaryController extends Controller
{
    public function index()
    {
        $summaries = EpisodeSummary::query()
            ->latest('published_at')
            ->latest()
            ->get();

        return view('summaries.index', [
            'summaries' => $summaries,
        ]);
    }

    public function fetchLatest(GenerateLatestSummaryService $generateLatestSummaryService)
    {
        try {
            $generateLatestSummaryService->run();

            return redirect()->route('summaries.index')->with('success', 'Neue ZIB1 Zusammenfassung gespeichert.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('summaries.index')->with('error', $exception->getMessage());
        }
    }

    public function ask(Request $request, EpisodeSummary $summary, AiSummaryQuestionService $aiSummaryQuestionService)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ], [
            'question.required' => 'Bitte gib eine Frage ein.',
            'question.max' => 'Die Frage darf maximal 500 Zeichen lang sein.',
        ]);

        try {
            $answer = $aiSummaryQuestionService->answer(
                summary: (string) $summary->summary,
                question: (string) $validated['question'],
            );

            return redirect()->route('summaries.index')->with('qa', [
                'summary_id' => $summary->id,
                'question' => $validated['question'],
                'answer' => $answer,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('summaries.index')
                ->withInput([
                    'question' => $validated['question'],
                    'qa_summary_id' => $summary->id,
                ])
                ->with('error', $exception->getMessage());
        }
    }

    public function destroy(EpisodeSummary $summary)
    {
        $summary->delete();

        return redirect()->route('summaries.index')->with('success', 'Eintrag wurde geloescht.');
    }

    public function prices(MarketPriceService $marketPriceService): JsonResponse
    {
        try {
            return response()->json($marketPriceService->current());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Marktpreise konnten nicht geladen werden.',
            ], 503);
        }
    }
}
