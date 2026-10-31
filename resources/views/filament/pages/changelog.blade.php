<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Hero Summary Banner (Waldo-inspired Dark Gradient + Amber Glow) -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-[#131b2e] p-6 md:p-8 text-white shadow-xl border border-slate-800">
            <!-- Decorative Amber Glow -->
            <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                        <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Active Release v{{ $this->getCurrentVersion() }}
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-white">
                        System Evolution & Release History
                    </h2>
                    <p class="text-sm text-slate-300 max-w-2xl leading-relaxed">
                        Track feature additions, security implementations, user resource evolutions, and architectural improvements across the application.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="px-4 py-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs text-center min-w-[90px]">
                        <span class="block text-2xl font-bold text-white">{{ count($this->releases) }}</span>
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider">Releases</span>
                    </div>
                    <div class="px-4 py-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs text-center min-w-[90px]">
                        <span class="block text-2xl font-bold text-amber-400">SemVer</span>
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider">Standard</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Release Timeline (Waldo-style vertical line + dots) -->
        <div class="relative pl-4 md:pl-8 space-y-8 before:absolute before:left-[15px] md:before:left-[31px] before:top-4 before:bottom-4 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
            @forelse ($this->releases as $release)
                <div class="relative group">
                    <!-- Timeline Dot -->
                    <div class="absolute -left-[23px] md:-left-[39px] top-6 flex h-4 w-4 items-center justify-center rounded-full bg-white dark:bg-slate-900 border-2 {{ $loop->first ? 'border-amber-500 ring-4 ring-amber-500/20' : 'border-slate-400 dark:border-slate-600' }}">
                        @if ($loop->first)
                            <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
                        @endif
                    </div>

                    <!-- Release Card -->
                    <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900/90 p-5 md:p-6 shadow-xs hover:shadow-md transition-all duration-200">
                        <!-- Header Row -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800/60">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="px-3 py-1 rounded-lg font-mono text-sm font-bold {{ $loop->first ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200' }}">
                                    v{{ ltrim($release->version, 'vV') }}
                                </span>

                                @php
                                    $typeBadgeClasses = match($release->type) {
                                        'major' => 'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200/60 dark:border-red-800/40',
                                        'minor' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40',
                                        default => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $typeBadgeClasses }}">
                                    {{ $release->badge ?? ucfirst($release->type) }}
                                </span>

                                @if ($loop->first)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Active
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 text-slate-400" />
                                <time datetime="{{ $release->released_at?->toDateString() }}">
                                    {{ $release->released_at ? $release->released_at->format('F d, Y') : $release->created_at->format('F d, Y') }}
                                </time>
                            </div>
                        </div>

                        <!-- Title & Description -->
                        <div class="mt-4 space-y-2">
                            <h3 class="text-base md:text-lg font-bold text-slate-900 dark:text-white">
                                {{ $release->title }}
                            </h3>

                            @if (!empty($release->description))
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 border-l-4 border-amber-500 px-4 py-2.5 rounded-r-xl">
                                    {{ $release->description }}
                                </p>
                            @endif

                            @if (!empty($release->highlights) && is_array($release->highlights))
                                <ul class="mt-3 space-y-1.5 pl-1">
                                    @foreach ($release->highlights as $highlight)
                                        <li class="flex items-start gap-2.5 text-xs md:text-sm text-slate-700 dark:text-slate-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-2 shrink-0"></span>
                                            <span>{!! preg_replace('/`([^`]+)`/', '<code class="px-1.5 py-0.5 rounded-md text-[11px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">$1</code>', e($highlight)) !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 p-12 text-center">
                    <x-heroicon-o-sparkles class="mx-auto h-8 w-8 text-slate-400" />
                    <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">No releases logged</h3>
                    <p class="mt-1 text-xs text-slate-500">Create a new release to populate the timeline.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
