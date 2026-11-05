<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog & Release Notes | Saving Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                            950: '#451a03',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full bg-slate-950 font-sans antialiased text-slate-100 selection:bg-amber-500 selection:text-white">
    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 font-bold text-lg text-white hover:opacity-90 transition group">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20 font-mono text-sm">
                    S
                </span>
                <span>Saving Portal</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/25">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    v{{ $currentVersion }}
                </span>
            </a>

            <div class="flex items-center gap-4">
                <a href="/" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5">
                    <span>Back to Portal</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">
        <!-- Waldo-Inspired Hero Card with Ambient Glow -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-[#131b2e] p-6 sm:p-10 text-white shadow-2xl border border-slate-800/90">
            <!-- Ambient Glow -->
            <div class="absolute -top-16 -right-16 w-80 h-80 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                        Active Release v{{ $currentVersion }}
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                        System Evolution & Releases
                    </h1>

                    <p class="text-sm sm:text-base text-slate-300 max-w-2xl leading-relaxed">
                        Track feature milestones, authentication evolutions, mobile screen optimizations, and platform updates documented in <code class="px-1.5 py-0.5 rounded-md text-xs font-mono bg-slate-800 text-amber-300 border border-slate-700">CHANGELOG.md</code>.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="px-5 py-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md text-center min-w-[95px]">
                        <span class="block text-2xl font-bold text-white">{{ count($releases) }}</span>
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Releases</span>
                    </div>
                    <div class="px-5 py-3.5 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md text-center min-w-[95px]">
                        <span class="block text-2xl font-bold text-amber-400">SemVer</span>
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Standard</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Release Timeline (Waldo Design Style) -->
        <div class="relative pl-6 sm:pl-10 space-y-10 before:absolute before:left-[19px] sm:before:left-[31px] before:top-5 before:bottom-5 before:w-0.5 before:bg-slate-800">
            @forelse ($releases as $release)
                <article class="relative group">
                    <!-- Timeline Node -->
                    <div class="absolute -left-[27px] sm:-left-[39px] top-6 flex h-4 w-4 items-center justify-center rounded-full bg-slate-950 border-2 {{ $loop->first ? 'border-amber-500 ring-4 ring-amber-500/20' : 'border-slate-700' }}">
                        @if ($loop->first)
                            <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
                        @endif
                    </div>

                    <!-- Release Card -->
                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/80 backdrop-blur-sm p-6 sm:p-7 shadow-lg transition duration-200 hover:border-amber-500/40 hover:shadow-amber-500/5">
                        <!-- Header Row -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800/80">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-mono text-xs sm:text-sm font-bold {{ $loop->first ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-sm' : 'bg-slate-800/80 text-slate-200 border border-slate-700' }}">
                                    @if ($loop->first)
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    @endif
                                    v{{ ltrim($release['version'], 'vV') }}
                                </span>

                                @php
                                    $badgeStyle = match($release['type']) {
                                        'major' => 'bg-red-950/60 text-red-300 border-red-800/50',
                                        'minor' => 'bg-amber-950/60 text-amber-300 border-amber-800/50',
                                        default => 'bg-emerald-950/60 text-emerald-300 border-emerald-800/50',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                                    {{ $release['badge'] }}
                                </span>

                                @if ($loop->first)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-800/40 px-2 py-0.5 rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Active
                                    </span>
                                @endif
                            </div>

                            @if (!empty($release['date']))
                                <div class="flex items-center gap-1.5 text-xs text-slate-400">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <time datetime="{{ $release['date'] }}">
                                        {{ date('F d, Y', strtotime($release['date'])) }}
                                    </time>
                                </div>
                            @endif
                        </div>

                        <!-- Rendered Markdown Body -->
                        <div class="mt-4 changelog-body space-y-3 text-sm text-slate-300 leading-relaxed">
                            {!! $release['html'] !!}
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-800 p-12 text-center">
                    <p class="text-sm font-medium text-slate-400">No releases logged in CHANGELOG.md yet.</p>
                </div>
            @endforelse
        </div>
    </main>

    <footer class="mt-20 border-t border-slate-800/80 py-8 text-center text-xs text-slate-500">
        <p>Saving Portal &bull; Release Version v{{ $currentVersion }}</p>
    </footer>

    <style>
        .changelog-body h3 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #fbbf24;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .changelog-body h3:first-child {
            margin-top: 0;
        }
        .changelog-body ul {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .changelog-body ul li {
            position: relative;
            padding-left: 1.25rem;
            font-size: 0.875rem;
            color: #cbd5e1;
        }
        .changelog-body ul li::before {
            content: "";
            position: absolute;
            left: 0.25rem;
            top: 0.55rem;
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 9999px;
            background-color: #f59e0b;
        }
        .changelog-body code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.375rem;
            background-color: #1e293b;
            color: #fde68a;
            border: 1px solid #334155;
        }
        .changelog-body p {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
    </style>
</body>
</html>
