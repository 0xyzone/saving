<x-filament-panels::page>
    <div class="space-y-6">
        @forelse ($this->changelogs as $log)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900 sm:p-6">
                <!-- Top Row: Badge, Version, Type, and Date -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-lg bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-950/60 dark:text-primary-300 dark:ring-primary-400/20">
                            {{ $log->version }}
                        </span>

                        @php
                            $typeClasses = match($log->type) {
                                'major' => 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-950/50 dark:text-red-400 dark:ring-red-500/20',
                                'minor' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-500/20',
                                'macro' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/20',
                                default => 'bg-gray-50 text-gray-700 ring-gray-600/10 dark:bg-gray-800 dark:text-gray-300',
                            };
                            $typeLabel = match($log->type) {
                                'major' => 'Major Release',
                                'minor' => 'Minor Release',
                                'macro' => 'Macro (Patch)',
                                default => ucfirst($log->type),
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $typeClasses }}">
                            {{ $typeLabel }}
                        </span>
                    </div>

                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ $log->released_at ? $log->released_at->format('M d, Y') : $log->created_at->format('M d, Y') }}
                    </div>
                </div>

                <!-- Title & Description Body -->
                <div class="pt-4 space-y-2">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">
                        {{ $log->title }}
                    </h3>

                    @if ($log->description)
                        <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-300 whitespace-pre-line text-sm leading-relaxed">
                            {{ $log->description }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-800">
                <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                    <x-heroicon-o-sparkles class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                </div>
                <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No releases logged yet</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add a new release using the button above.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
