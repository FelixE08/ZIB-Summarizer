<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MarketPriceService
{
    public function current(): array
    {
        return Cache::remember('market-prices:gold-brent', now()->addMinutes(5), function (): array {
            $inflation = $this->fetchAustriaInflationRateLastMonth();

            $providers = [
                'stooq' => fn (): array => $this->fetchFromStooq(),
                'yahoo' => fn (): array => $this->fetchFromYahoo(),
            ];

            $lastError = null;

            foreach ($providers as $providerName => $provider) {
                try {
                    $prices = $provider();

                    return [
                        'gold_usd_per_ounce' => $prices['gold'],
                        'brent_usd_per_barrel' => $prices['brent'],
                        'inflation_at_last_month_percent' => $inflation['value'],
                        'inflation_reference_month' => $inflation['month'],
                        'fetched_at' => now()->toIso8601String(),
                        'source' => $providerName,
                    ];
                } catch (Throwable $exception) {
                    $lastError = $exception;
                    Log::warning('Marktpreis-Provider fehlgeschlagen.', [
                        'provider' => $providerName,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            if ($lastError instanceof Throwable) {
                throw new RuntimeException(
                    'Marktpreise konnten nicht geladen werden: '.$lastError->getMessage(),
                    previous: $lastError
                );
            }

            throw new RuntimeException('Marktpreise sind aktuell nicht verfuegbar.');
        });
    }

    private function fetchAustriaInflationRateLastMonth(): array
    {
        $targetMonth = now()->subMonth()->format('Y-m');

        $response = Http::timeout(12)
            ->acceptJson()
            ->get('https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/prc_hicp_minr', [
                'geo' => 'AT',
                'unit' => 'RCH_A',
                'coicop18' => 'TOTAL',
                'time' => $targetMonth,
            ]);

        if (! $response->ok()) {
            throw new RuntimeException('Eurostat HTTP '.$response->status());
        }

        $values = (array) data_get($response->json(), 'value', []);
        $targetValue = $values['0'] ?? null;

        if ($targetValue !== null && ! is_numeric($targetValue)) {
            throw new RuntimeException('Eurostat Inflationswert ist ungueltig.');
        }

        return [
            'value' => is_numeric($targetValue) ? round((float) $targetValue, 1) : null,
            'month' => $targetMonth,
        ];
    }

    private function fetchFromYahoo(): array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get('https://query1.finance.yahoo.com/v7/finance/quote', [
                'symbols' => 'GC=F,BZ=F',
            ]);

        if (! $response->ok()) {
            throw new RuntimeException('Yahoo HTTP '.$response->status());
        }

        $results = collect((array) data_get($response->json(), 'quoteResponse.result', []))
            ->keyBy('symbol');

        $gold = $this->formatYahooPrice($results->get('GC=F'));
        $brent = $this->formatYahooPrice($results->get('BZ=F'));

        if ($gold === null || $brent === null) {
            throw new RuntimeException('Yahoo lieferte unvollstaendige Daten.');
        }

        return [
            'gold' => $gold,
            'brent' => $brent,
        ];
    }

    private function fetchFromStooq(): array
    {
        $gold = $this->fetchStooqSymbolPrice('xauusd');
        $brent = $this->fetchStooqSymbolPrice('cb.f');

        return [
            'gold' => $gold,
            'brent' => $brent,
        ];
    }

    private function fetchStooqSymbolPrice(string $symbol): float
    {
        $response = Http::timeout(10)
            ->accept('text/csv')
            ->get('https://stooq.com/q/l/', [
                's' => $symbol,
                'f' => 'sd2t2ohlcv',
                'h' => '',
                'e' => 'csv',
            ]);

        if (! $response->ok()) {
            throw new RuntimeException("Stooq HTTP {$response->status()} fuer {$symbol}.");
        }

        $rows = preg_split('/\r\n|\r|\n/', trim($response->body())) ?: [];

        if (count($rows) < 2) {
            throw new RuntimeException("Stooq lieferte keine Daten fuer {$symbol}.");
        }

        $columns = str_getcsv($rows[1]);
        $close = $columns[6] ?? null;

        if (! is_numeric($close)) {
            throw new RuntimeException("Stooq Close-Wert ungueltig fuer {$symbol}.");
        }

        return round((float) $close, 2);
    }

    private function formatYahooPrice(mixed $quote): ?float
    {
        $price = data_get($quote, 'regularMarketPrice');

        if (! is_numeric($price)) {
            return null;
        }

        return round((float) $price, 2);
    }
}
