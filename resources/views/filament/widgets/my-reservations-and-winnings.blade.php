<x-filament-widgets::widget>
    <div id="my-disbursed-rounds-section" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- My Disbursed Rounds & Payouts --}}
        <x-filament::section>
            <x-slot name="heading">
                My Disbursed Rounds &amp; Payouts
            </x-slot>

            @if ($winnings->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    You have not received a round disbursement in your ongoing schemes yet.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($winnings as $period)
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $period->scheme?->title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Period #{{ $period->period_number }}
                                        &middot; Seat #{{ $period->winningSeat?->seat_number ?? '-' }}
                                        &middot; {{ $period->formatted_due_date_bs }}
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $period->payout_status === 'paid',
                                    'bg-amber-500/10 text-amber-600 dark:text-amber-400' => $period->payout_status === 'partial',
                                    'bg-gray-500/10 text-gray-600 dark:text-gray-400' => ! in_array($period->payout_status, ['paid', 'partial'], true),
                                ])>
                                    {{ ucfirst($period->payout_status ?? 'pending') }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-100 pt-3 text-xs dark:border-white/10">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Net Disbursement</span>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        Rs. {{ number_format((float) $period->net_payout_amount, 2) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Received</span>
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">
                                        Rs. {{ number_format((float) $period->paid_amount, 2) }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Balance</span>
                                    <p class="font-semibold text-amber-600 dark:text-amber-400">
                                        Rs. {{ number_format((float) $period->winner_balance_remaining, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- My Period Reservations --}}
        <x-filament::section>
            <x-slot name="heading">
                My Period Reservations
            </x-slot>

            @if ($reservations->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    No period reservation requests found for your ongoing schemes.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($reservations as $reservation)
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $reservation->scheme?->title }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Requested Period #{{ $reservation->period?->period_number ?? '-' }}
                                        @if ($reservation->seat)
                                            &middot; Seat #{{ $reservation->seat->seat_number }}
                                        @endif
                                        @if ($reservation->period)
                                            &middot; {{ $reservation->period->formatted_due_date_bs }}
                                        @endif
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $reservation->status === 'approved',
                                    'bg-amber-500/10 text-amber-600 dark:text-amber-400' => $reservation->status === 'pending',
                                    'bg-rose-500/10 text-rose-600 dark:text-rose-400' => $reservation->status === 'rejected',
                                ])>
                                    {{ ucfirst($reservation->status ?? 'pending') }}
                                </span>
                            </div>

                            @if (filled($reservation->reason))
                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $reservation->reason }}
                                </p>
                            @endif

                            @if (filled($reservation->admin_notes))
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    Note: {{ $reservation->admin_notes }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
