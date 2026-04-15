<?php

use App\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SummaryController::class, 'index'])->name('summaries.index');
Route::get('/market/prices', [SummaryController::class, 'prices'])->name('summaries.prices');
Route::post('/summaries/fetch-latest', [SummaryController::class, 'fetchLatest'])->name('summaries.fetch-latest');
Route::post('/summaries/{summary}/ask', [SummaryController::class, 'ask'])->name('summaries.ask');
Route::delete('/summaries/{summary}', [SummaryController::class, 'destroy'])->name('summaries.destroy');
