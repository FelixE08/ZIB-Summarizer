<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZIB1 Zusammenfassung</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: "Airstrike";
            src: local("Airstrike"), local("Airstrike 3D"), url("/airstrike.ttf") format("truetype");
            font-style: normal;
            font-weight: 400;
            font-display: swap;
        }

        :root {
            color-scheme: dark;
        }

        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background:
                radial-gradient(60rem 36rem at 88% 15%, rgba(32, 250, 197, 0.24), transparent 60%),
                radial-gradient(54rem 30rem at 8% 95%, rgba(14, 97, 82, 0.18), transparent 56%),
                linear-gradient(180deg, #020709 0%, #040c0f 100%);
            min-height: 100vh;
        }

        .grid-line-overlay {
            background-image:
                linear-gradient(to right, rgba(95, 124, 132, 0.17) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(95, 124, 132, 0.17) 1px, transparent 1px);
            background-size: 7.5rem 7.5rem;
            mask-image: radial-gradient(circle at center, black 42%, transparent 100%);
            pointer-events: none;
        }

        .surface-shell {
            border: 1px solid rgba(118, 143, 150, 0.35);
            border-radius: 2rem;
            background: linear-gradient(180deg, rgba(8, 20, 24, 0.92) 0%, rgba(6, 16, 20, 0.9) 100%);
            box-shadow:
                0 0 0 1px rgba(164, 186, 192, 0.12) inset,
                0 30px 90px rgba(0, 0, 0, 0.55),
                0 0 90px rgba(8, 194, 164, 0.12);
        }

        .panel-card {
            border: 1px solid rgba(89, 111, 119, 0.4);
            background: linear-gradient(180deg, rgba(10, 24, 29, 0.9) 0%, rgba(8, 19, 23, 0.86) 100%);
            box-shadow: 0 0 0 1px rgba(136, 161, 169, 0.08) inset;
        }

        .pill-tag {
            border: 1px solid rgba(84, 109, 117, 0.6);
            background: rgba(17, 41, 49, 0.78);
            color: rgba(173, 211, 220, 0.95);
        }

        .accent-button {
            background: linear-gradient(90deg, #14b8a6 0%, #21d7b8 100%);
            color: #022018;
            box-shadow: 0 10px 24px rgba(20, 184, 166, 0.3);
        }

        .news-headline {
            font-family: "Airstrike", Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            letter-spacing: 0.01em;
            font-weight: 400;
        }
    </style>
</head>
<body class="relative min-h-screen text-slate-100">
    <div class="grid-line-overlay fixed inset-0 z-0"></div>
    <main class="relative z-10 mx-auto max-w-7xl px-6 py-10">
        <div class="surface-shell px-6 py-7 md:px-8 md:py-8">
        <div class="grid items-start gap-8 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-8">
                <section class="panel-card rounded-2xl p-5">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-100">Marktpreise</h2>
                    <p class="mt-1 text-sm text-slate-400">Live-Daten für Gold und Brent.</p>
                    <div id="market-prices-panel" class="mt-4 space-y-3">
                        <div class="rounded-xl border border-emerald-300/15 bg-emerald-400/5 p-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-emerald-200/80">Gold (Feinunze)</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-100" data-gold-price>--</p>
                        </div>
                        <div class="rounded-xl border border-cyan-300/15 bg-cyan-400/5 p-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-cyan-200/80">Öl Brent (Barrel)</p>
                            <p class="mt-1 text-2xl font-semibold text-cyan-100" data-brent-price>--</p>
                        </div>
                        <div class="rounded-xl border border-violet-300/15 bg-violet-400/5 p-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-violet-200/80">Inflation Österreich (letzter Monat)</p>
                            <p class="mt-1 text-2xl font-semibold text-violet-100" data-inflation-rate>--</p>
                            <p class="mt-1 text-xs text-violet-200/70" data-inflation-month>Monat: --</p>
                        </div>
                        <p class="text-xs text-slate-500" data-prices-updated>Wird geladen ...</p>
                        <p class="hidden text-xs text-rose-300" data-prices-error>Marktpreise konnten nicht geladen werden.</p>
                    </div>
                </section>
            </aside>
            <div class="space-y-8">
        <section class="panel-card rounded-2xl p-6">
            <h1 class="news-headline text-4xl tracking-tight">ZIB Zusammenfassung</h1>
            <p class="mt-3 text-slate-300/90">
                Immer die letzte ZIB1 Folge laden, per AI vollständig zusammenfassen und in der Datenbank speichern.
            </p>

            <form id="fetch-latest-form" action="{{ route('summaries.fetch-latest') }}" method="POST" class="mt-6">
                @csrf
                <button
                    id="fetch-latest-button"
                    type="submit"
                    class="accent-button rounded-xl px-5 py-2.5 font-semibold transition hover:brightness-110"
                >
                    Letzte ZIB-Sendung zusammenfassen
                </button>
            </form>

            @if (session('success'))
                <div class="mt-4 rounded-lg border border-emerald-700 bg-emerald-900/30 px-4 py-3 text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-4 rounded-lg border border-rose-700 bg-rose-900/30 px-4 py-3 text-rose-200">
                    {{ session('error') }}
                </div>
            @endif
        </section>

        <section class="space-y-4">
            @forelse($summaries as $summary)
                @php
                    $shouldExpand = (session('qa') && data_get(session('qa'), 'summary_id') == $summary->id)
                        || old('qa_summary_id') == $summary->id;
                @endphp
                <article class="panel-card rounded-2xl p-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 text-left"
                        data-toggle-section
                        data-target-id="entry-body-{{ $summary->id }}"
                        aria-expanded="{{ $shouldExpand ? 'true' : 'false' }}"
                    >
                        <h2 class="text-lg font-semibold text-slate-100">{{ $summary->source_title }}</h2>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-slate-300 transition-transform {{ $shouldExpand ? 'rotate-90' : '' }}" data-chevron aria-hidden="true">
                            <path fill="currentColor" d="M8.47 4.97a.75.75 0 0 0-1.06 1.06L13.38 12l-5.97 5.97a.75.75 0 1 0 1.06 1.06l6.5-6.5a.75.75 0 0 0 0-1.06l-6.5-6.5Z"/>
                        </svg>
                    </button>
                    <div id="entry-body-{{ $summary->id }}" class="mt-4 {{ $shouldExpand ? '' : 'hidden' }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-400">
                                Gespeichert: {{ $summary->created_at->format('d.m.Y H:i') }}
                            </p>
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-sm text-cyan-300 transition hover:text-cyan-200"
                                    data-download-pdf
                                    data-title="{{ $summary->source_title }}"
                                    data-created-at="{{ $summary->created_at->format('d.m.Y H:i') }}"
                                    data-source-url="{{ $summary->source_url }}"
                                    data-summary-base64="{{ base64_encode($summary->summary) }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                        <path d="M12 3a.75.75 0 0 1 .75.75v9.19l2.22-2.22a.75.75 0 1 1 1.06 1.06l-3.5 3.5a.75.75 0 0 1-1.06 0l-3.5-3.5a.75.75 0 1 1 1.06-1.06l2.22 2.22V3.75A.75.75 0 0 1 12 3Zm-7 13.5A1.5 1.5 0 0 1 6.5 15h11a1.5 1.5 0 0 1 1.5 1.5v2A2.5 2.5 0 0 1 16.5 21h-9A2.5 2.5 0 0 1 5 18.5v-2Zm1.5 0v2A1 1 0 0 0 7.5 19h9a1 1 0 0 0 1-1v-2h-11Z"/>
                                    </svg>
                                    <span>PDF herunterladen</span>
                                </button>
                                <a href="{{ $summary->source_url }}" target="_blank" class="text-sm text-indigo-300 hover:text-indigo-200">
                                    Quelle ansehen
                                </a>
                                <form action="{{ route('summaries.destroy', $summary) }}" method="POST" onsubmit="return confirm('Eintrag wirklich löschen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-700/70 bg-rose-900/30 text-rose-300 transition hover:bg-rose-800/40 hover:text-rose-200"
                                        title="Eintrag löschen"
                                        aria-label="Eintrag löschen"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                            <path d="M9 3a1 1 0 0 0-1 1v1H5.75a.75.75 0 0 0 0 1.5h.67l.7 11.24A2.5 2.5 0 0 0 9.61 20h4.78a2.5 2.5 0 0 0 2.49-2.26l.7-11.24h.67a.75.75 0 0 0 0-1.5H16V4a1 1 0 0 0-1-1H9Zm1.5 2V4.5h3V5h-3Zm-.75 4a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6A.75.75 0 0 1 9.75 9Zm4.5 0a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6a.75.75 0 0 1 .75-.75Z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <button
                        type="button"
                        class="mt-4 inline-flex items-center gap-2 text-sm text-slate-300 transition hover:text-slate-100"
                        data-toggle-summary
                        data-target-id="summary-{{ $summary->id }}"
                        aria-expanded="false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 transition-transform" data-chevron aria-hidden="true">
                            <path fill="currentColor" d="M8.47 4.97a.75.75 0 0 0-1.06 1.06L13.38 12l-5.97 5.97a.75.75 0 1 0 1.06 1.06l6.5-6.5a.75.75 0 0 0 0-1.06l-6.5-6.5Z"/>
                        </svg>
                        <span>Zusammenfassung anzeigen</span>
                    </button>
                    <div id="summary-{{ $summary->id }}" class="prose prose-invert mt-4 hidden max-w-none whitespace-pre-wrap text-slate-200 [&_h1]:text-3xl [&_h1]:font-bold [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:text-xl [&_h3]:font-bold">
                        {!! \Illuminate\Support\Str::markdown($summary->summary, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                    <div class="mt-5 rounded-xl border border-slate-600/45 bg-slate-950/45 p-4">
                        <h3 class="text-sm font-semibold text-slate-200">Frage zur Zusammenfassung stellen</h3>
                        <form action="{{ route('summaries.ask', $summary) }}" method="POST" class="mt-3 space-y-3" data-ask-form>
                            @csrf
                            <input type="hidden" name="qa_summary_id" value="{{ $summary->id }}">
                            <textarea
                                name="question"
                                rows="3"
                                maxlength="500"
                                class="w-full rounded-lg border border-slate-600/60 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-emerald-300/70 focus:outline-none focus:ring-1 focus:ring-emerald-300/70"
                                placeholder="z. B. Wie ist der internationale Hintergrund dazu?"
                                required
                            >{{ old('qa_summary_id') == $summary->id ? old('question') : '' }}</textarea>
                            <button
                                type="submit"
                                class="accent-button inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition hover:brightness-110"
                                data-ask-submit
                            >
                                <span>Frage an KI senden</span>
                                <svg class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" data-ask-spinner aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" class="opacity-30" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-100" fill="currentColor" d="M22 12a10 10 0 0 0-10-10v4a6 6 0 0 1 6 6h4Z"></path>
                                </svg>
                            </button>
                        </form>

                        @if ($errors->has('question') && old('qa_summary_id') == $summary->id)
                            <div class="mt-3 rounded-lg border border-rose-700 bg-rose-900/30 px-3 py-2 text-sm text-rose-200">
                                {{ $errors->first('question') }}
                            </div>
                        @endif

                        @if (session('qa') && data_get(session('qa'), 'summary_id') == $summary->id)
                            <div class="mt-4 rounded-lg border border-indigo-700/60 bg-indigo-900/20 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-indigo-300">Deine Frage</p>
                                <p class="mt-1 text-sm text-slate-200">{{ data_get(session('qa'), 'question') }}</p>
                                <p class="mt-3 text-xs uppercase tracking-wide text-indigo-300">KI Antwort</p>
                                <div class="mt-1 prose prose-invert max-w-none text-sm text-slate-100">
                                    {!! \Illuminate\Support\Str::markdown(data_get(session('qa'), 'answer', ''), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-600/60 bg-slate-900/45 p-8 text-center text-slate-400">
                    Noch keine Zusammenfassungen vorhanden.
                </div>
            @endforelse
        </section>
            </div>
        </div>
        </div>
    </main>
    <div id="loading-overlay" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl border border-indigo-400/30 bg-slate-900/90 p-6 text-center shadow-2xl shadow-indigo-500/10">
            <div class="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-indigo-300/30 border-t-indigo-300"></div>
            <p class="mt-4 text-lg font-semibold text-indigo-200">Zusammenfassung wird erstellt</p>
            <p class="mt-1 text-sm text-slate-300">Bitte kurz warten ...</p>
        </div>
    </div>
    <div id="qa-loading-overlay" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-2xl border border-cyan-400/30 bg-slate-900/90 p-6 text-center shadow-2xl shadow-cyan-500/10">
            <div class="mx-auto flex h-14 items-center justify-center gap-2">
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-cyan-300 [animation-delay:-0.2s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-cyan-300 [animation-delay:-0.1s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-cyan-300"></span>
            </div>
            <p class="mt-4 text-lg font-semibold text-cyan-200">KI beantwortet deine Frage</p>
            <p class="mt-1 text-sm text-slate-300">Einen Moment bitte ...</p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        const fetchLatestForm = document.getElementById('fetch-latest-form');
        const fetchLatestButton = document.getElementById('fetch-latest-button');
        const loadingOverlay = document.getElementById('loading-overlay');
        const qaLoadingOverlay = document.getElementById('qa-loading-overlay');
        const marketPriceUrl = "{{ route('summaries.prices') }}";
        const goldPriceEl = document.querySelector('[data-gold-price]');
        const brentPriceEl = document.querySelector('[data-brent-price]');
        const inflationRateEl = document.querySelector('[data-inflation-rate]');
        const inflationMonthEl = document.querySelector('[data-inflation-month]');
        const pricesUpdatedEl = document.querySelector('[data-prices-updated]');
        const pricesErrorEl = document.querySelector('[data-prices-error]');

        const formatUsd = (value) => {
            if (typeof value !== 'number' || Number.isNaN(value)) {
                return '--';
            }

            return new Intl.NumberFormat('de-AT', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value);
        };

        const setPricesErrorState = (hasError) => {
            if (!pricesErrorEl) {
                return;
            }

            pricesErrorEl.classList.toggle('hidden', !hasError);
        };

        const formatPercent = (value) => {
            if (typeof value !== 'number' || Number.isNaN(value)) {
                return '--';
            }

            return `${new Intl.NumberFormat('de-AT', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            }).format(value)} %`;
        };

        const formatMonth = (month) => {
            if (typeof month !== 'string' || !/^\d{4}-\d{2}$/.test(month)) {
                return 'Monat: --';
            }

            const [year, monthNumber] = month.split('-').map(Number);
            const date = new Date(year, monthNumber - 1, 1);

            return `Monat: ${date.toLocaleDateString('de-AT', { month: 'long', year: 'numeric' })}`;
        };

        const updateMarketPrices = async () => {
            if (!goldPriceEl || !brentPriceEl || !inflationRateEl || !inflationMonthEl || !pricesUpdatedEl) {
                return;
            }

            try {
                const response = await fetch(marketPriceUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('request failed');
                }

                const data = await response.json();

                goldPriceEl.textContent = formatUsd(Number(data.gold_usd_per_ounce));
                brentPriceEl.textContent = formatUsd(Number(data.brent_usd_per_barrel));
                const inflationRawValue = data.inflation_at_last_month_percent;
                const inflationNumericValue = inflationRawValue === null || inflationRawValue === undefined
                    ? null
                    : Number(inflationRawValue);
                inflationRateEl.textContent = formatPercent(inflationNumericValue);
                inflationMonthEl.textContent = formatMonth(data.inflation_reference_month);

                const fetchedAt = data.fetched_at ? new Date(data.fetched_at) : null;
                pricesUpdatedEl.textContent = fetchedAt && !Number.isNaN(fetchedAt.valueOf())
                    ? `Stand: ${fetchedAt.toLocaleString('de-AT')}`
                    : 'Stand: gerade eben';

                setPricesErrorState(false);
            } catch (error) {
                pricesUpdatedEl.textContent = 'Stand: nicht verfügbar';
                inflationRateEl.textContent = '--';
                inflationMonthEl.textContent = 'Monat: --';
                setPricesErrorState(true);
            }
        };

        updateMarketPrices();
        setInterval(updateMarketPrices, 5 * 60 * 1000);

        if (fetchLatestForm && fetchLatestButton && loadingOverlay) {
            fetchLatestForm.addEventListener('submit', () => {
                fetchLatestButton.setAttribute('disabled', 'disabled');
                fetchLatestButton.classList.add('cursor-not-allowed', 'opacity-70');
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex');
                loadingOverlay.classList.remove('pointer-events-none');
            });
        }

        document.querySelectorAll('[data-ask-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const submitButton = form.querySelector('[data-ask-submit]');
                const spinner = form.querySelector('[data-ask-spinner]');
                if (submitButton) {
                    submitButton.setAttribute('disabled', 'disabled');
                    submitButton.classList.add('cursor-not-allowed', 'opacity-80');
                }
                if (spinner) {
                    spinner.classList.remove('hidden');
                }
                if (qaLoadingOverlay) {
                    qaLoadingOverlay.classList.remove('hidden');
                    qaLoadingOverlay.classList.add('flex');
                    qaLoadingOverlay.classList.remove('pointer-events-none');
                }
            });
        });

        const animateSection = (content, expand) => {
            content.classList.remove('hidden');
            content.style.overflow = 'hidden';
            content.style.transition = 'max-height 220ms ease, opacity 220ms ease';

            if (expand) {
                content.style.maxHeight = '0px';
                content.style.opacity = '0';
                requestAnimationFrame(() => {
                    content.style.maxHeight = `${content.scrollHeight}px`;
                    content.style.opacity = '1';
                });
            } else {
                content.style.maxHeight = `${content.scrollHeight}px`;
                content.style.opacity = '1';
                requestAnimationFrame(() => {
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                });
            }

            const onEnd = () => {
                if (!expand) {
                    content.classList.add('hidden');
                }
                content.style.removeProperty('max-height');
                content.style.removeProperty('opacity');
                content.style.removeProperty('overflow');
                content.style.removeProperty('transition');
                content.removeEventListener('transitionend', onEnd);
            };

            content.addEventListener('transitionend', onEnd);
        };

        const animateSummary = (content, expand) => {
            content.classList.remove('hidden');
            content.style.overflow = 'hidden';
            content.style.transition = 'max-height 180ms ease, opacity 180ms ease';

            if (expand) {
                content.style.maxHeight = '0px';
                content.style.opacity = '0';
                requestAnimationFrame(() => {
                    content.style.maxHeight = `${content.scrollHeight}px`;
                    content.style.opacity = '1';
                });
            } else {
                content.style.maxHeight = `${content.scrollHeight}px`;
                content.style.opacity = '1';
                requestAnimationFrame(() => {
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                });
            }

            const onEnd = () => {
                if (!expand) {
                    content.classList.add('hidden');
                }
                content.style.removeProperty('max-height');
                content.style.removeProperty('opacity');
                content.style.removeProperty('overflow');
                content.style.removeProperty('transition');
                content.removeEventListener('transitionend', onEnd);
            };

            content.addEventListener('transitionend', onEnd);
        };

        document.querySelectorAll('[data-toggle-section]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target-id');
                const content = document.getElementById(targetId);
                const chevron = button.querySelector('[data-chevron]');

                if (!content || !chevron) {
                    return;
                }

                const isHidden = content.classList.contains('hidden');
                animateSection(content, isHidden);
                button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                chevron.classList.toggle('rotate-90', isHidden);
            });
        });

        document.querySelectorAll('[data-toggle-summary]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target-id');
                const content = document.getElementById(targetId);
                const chevron = button.querySelector('[data-chevron]');
                const label = button.querySelector('span');

                if (!content || !chevron || !label) {
                    return;
                }

                const isHidden = content.classList.contains('hidden');
                animateSummary(content, isHidden);
                button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                chevron.classList.toggle('rotate-90', isHidden);
                label.textContent = isHidden ? 'Zusammenfassung ausblenden' : 'Zusammenfassung anzeigen';
            });
        });

        document.querySelectorAll('[data-download-pdf]').forEach((button) => {
            button.addEventListener('click', () => {
                const title = button.getAttribute('data-title') || 'ZIB1 Zusammenfassung';
                const createdAt = button.getAttribute('data-created-at') || '';
                const sourceUrl = button.getAttribute('data-source-url') || '';
                const summaryBase64 = button.getAttribute('data-summary-base64') || '';

                if (!window.jspdf || !window.jspdf.jsPDF) {
                    alert('PDF-Bibliothek konnte nicht geladen werden. Bitte Seite neu laden.');
                    return;
                }

                const decodeBase64Utf8 = (value) => {
                    try {
                        const binary = atob(value);
                        const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
                        return new TextDecoder().decode(bytes);
                    } catch (error) {
                        return '';
                    }
                };

                const normalizeMarkdownForPdf = (text) => {
                    return text
                        .replace(/__(.*?)__/g, '$1')
                        .replace(/`([^`]+)`/g, '$1')
                        .replace(/\[(.*?)\]\((.*?)\)/g, '$1 ($2)');
                };

                const writeParagraph = (doc, text, options) => {
                    const lines = doc.splitTextToSize(text, options.maxWidth);
                    lines.forEach((line) => {
                        if (options.yRef.value + options.lineHeight > options.pageBottom) {
                            doc.addPage();
                            options.yRef.value = options.pageTop;
                        }
                        doc.text(line, options.margin, options.yRef.value);
                        options.yRef.value += options.lineHeight;
                    });
                };

                const tokenizeInlineBold = (text) => {
                    const tokens = [];
                    const regex = /\*\*(.*?)\*\*/g;
                    let lastIndex = 0;
                    let match;

                    while ((match = regex.exec(text)) !== null) {
                        if (match.index > lastIndex) {
                            tokens.push({ text: text.slice(lastIndex, match.index), weight: 'normal' });
                        }
                        tokens.push({ text: match[1], weight: 'bold' });
                        lastIndex = regex.lastIndex;
                    }

                    if (lastIndex < text.length) {
                        tokens.push({ text: text.slice(lastIndex), weight: 'normal' });
                    }

                    return tokens.length > 0 ? tokens : [{ text, weight: 'normal' }];
                };

                const splitTokenToWordSegments = (token) => {
                    return token.text
                        .split(/(\s+)/)
                        .filter((part) => part.length > 0)
                        .map((part) => ({ text: part, weight: token.weight }));
                };

                const measureTextWidth = (doc, text, fontSize, weight) => {
                    doc.setFont('helvetica', weight);
                    doc.setFontSize(fontSize);
                    return doc.getTextWidth(text);
                };

                const writeRichParagraph = (doc, text, options) => {
                    const inlineTokens = tokenizeInlineBold(text);
                    const segments = inlineTokens.flatMap(splitTokenToWordSegments);
                    const lines = [];
                    let currentLine = [];
                    let currentWidth = 0;

                    segments.forEach((segment) => {
                        const width = measureTextWidth(doc, segment.text, options.fontSize, segment.weight);
                        const isWhitespace = /^\s+$/.test(segment.text);

                        if (currentLine.length === 0 && isWhitespace) {
                            return;
                        }

                        if (!isWhitespace && currentWidth + width > options.maxWidth && currentLine.length > 0) {
                            lines.push(currentLine);
                            currentLine = [];
                            currentWidth = 0;
                        }

                        currentLine.push(segment);
                        currentWidth += width;
                    });

                    if (currentLine.length > 0) {
                        lines.push(currentLine);
                    }

                    lines.forEach((lineSegments) => {
                        if (options.yRef.value + options.lineHeight > options.pageBottom) {
                            doc.addPage();
                            options.yRef.value = options.pageTop;
                        }

                        let x = options.margin;
                        lineSegments.forEach((segment) => {
                            if (segment.text === '') {
                                return;
                            }
                            doc.setFont('helvetica', segment.weight);
                            doc.setFontSize(options.fontSize);
                            doc.text(segment.text, x, options.yRef.value);
                            x += doc.getTextWidth(segment.text);
                        });
                        options.yRef.value += options.lineHeight;
                    });
                };

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ unit: 'mm', format: 'a4' });
                const summary = decodeBase64Utf8(summaryBase64);

                const margin = 16;
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const maxWidth = pageWidth - margin * 2;
                const pageTop = 20;
                const pageBottom = pageHeight - 16;
                const yRef = { value: pageTop };

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(16);
                const titleLines = doc.splitTextToSize(title, maxWidth);
                titleLines.forEach((line) => {
                    doc.text(line, margin, yRef.value);
                    yRef.value += 7;
                });
                yRef.value += 1;

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                if (createdAt) {
                    writeParagraph(doc, `Gespeichert: ${createdAt}`, {
                        maxWidth,
                        margin,
                        yRef,
                        lineHeight: 5,
                        pageTop,
                        pageBottom,
                    });
                }
                if (sourceUrl) {
                    writeParagraph(doc, `Quelle: ${sourceUrl}`, {
                        maxWidth,
                        margin,
                        yRef,
                        lineHeight: 5,
                        pageTop,
                        pageBottom,
                    });
                }
                yRef.value += 3;

                const blocks = normalizeMarkdownForPdf(summary)
                    .replace(/\r\n/g, '\n')
                    .split('\n')
                    .map((line) => line.trimEnd());

                blocks.forEach((rawLine) => {
                    const line = rawLine.trim();
                    if (line === '') {
                        yRef.value += 3;
                        return;
                    }

                    if (line.startsWith('# ')) {
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(14);
                        writeParagraph(doc, line.replace(/^#\s+/, ''), {
                            maxWidth,
                            margin,
                            yRef,
                            lineHeight: 6,
                            pageTop,
                            pageBottom,
                        });
                        yRef.value += 1;
                        return;
                    }

                    if (line.startsWith('## ')) {
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(12);
                        writeParagraph(doc, line.replace(/^##\s+/, ''), {
                            maxWidth,
                            margin,
                            yRef,
                            lineHeight: 5.5,
                            pageTop,
                            pageBottom,
                        });
                        yRef.value += 1;
                        return;
                    }

                    const isBullet = /^[-*]\s+/.test(line);
                    const bodyFontSize = 11;

                    if (isBullet) {
                        const bulletText = line.replace(/^[-*]\s+/, '');
                        writeRichParagraph(doc, `• ${bulletText}`, {
                            maxWidth,
                            margin,
                            yRef,
                            lineHeight: 5.3,
                            pageTop,
                            pageBottom,
                            fontSize: bodyFontSize,
                        });
                        return;
                    }

                    writeRichParagraph(doc, line, {
                        maxWidth,
                        margin,
                        yRef,
                        lineHeight: 5.3,
                        pageTop,
                        pageBottom,
                        fontSize: bodyFontSize,
                    });
                });

                const fileName = `${title.replace(/[\\/:*?"<>|]/g, '').slice(0, 60) || 'zib1-zusammenfassung'}.pdf`;
                doc.save(fileName);
            });
        });
    </script>
</body>
</html>
