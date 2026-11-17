<x-filament-panels::page>
    {{-- Header Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-4 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Installment Per Seat</div>
            <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format((float) $record->installment_amount) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Base contribution each round</div>
        </div>

        <div class="p-4 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-medium text-amber-500 dark:text-amber-400">Past Winner Interest</div>
            <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                +Rs. {{ number_format((float) $record->interest_amount) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Added to past winners' dues</div>
        </div>

        <div class="p-4 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-medium text-rose-500 dark:text-rose-400">Maslanda Deposit</div>
            <div class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400">
                -Rs. {{ number_format((float) $record->khaja_kharcha) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Deducted from winner (Period 2+)</div>
        </div>

        <div class="p-4 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-medium text-emerald-500 dark:text-emerald-400">Current Progress</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                Period {{ $record->current_period }} / {{ $record->total_periods }}
            </div>
            <div class="mt-1 text-xs text-slate-500">{{ $record->formatted_start_date_bs }} start</div>
        </div>
    </div>

    {{-- Main Tabbed Content --}}
    <div x-data="{ activeTab: 'periods' }" class="space-y-4">
        {{-- Tab Buttons --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
            <button type="button" @click="activeTab = 'periods'"
                :class="activeTab === 'periods' ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/40 dark:text-primary-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                class="px-3 py-1.5 text-sm rounded-lg transition">
                Periods & Winners ({{ $record->periods->count() }})
            </button>
            <button type="button" @click="activeTab = 'seats'"
                :class="activeTab === 'seats' ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/40 dark:text-primary-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                class="px-3 py-1.5 text-sm rounded-lg transition">
                Seats Roster ({{ $record->seats->count() }})
            </button>
            <button type="button" @click="activeTab = 'installments'"
                :class="activeTab === 'installments' ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/40 dark:text-primary-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                class="px-3 py-1.5 text-sm rounded-lg transition">
                Installment Collections
            </button>
        </div>

        {{-- TAB 1: Periods & Winners --}}
        <div x-show="activeTab === 'periods'" class="space-y-3">
            <div class="flex items-center justify-between pb-1">
                <div class="text-xs text-slate-500">
                    Track rounds, declared winners, reservations, and member settlements.
                </div>
                <button type="button" wire:click="refreshPeriodInstallments" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition shadow-sm border border-slate-200 dark:border-slate-700"
                    title="Scan all rounds and generate any missing installment records">
                    <svg wire:loading.remove wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <svg wire:loading wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Refresh Installments</span>
                </button>
            </div>
            {{-- Mobile Card View --}}
            <div class="sp-mobile-only space-y-3">
                @foreach ($record->periods as $period)
                    <div wire:click="openPeriodDetails({{ $period->id }})"
                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-3 cursor-pointer {{ $period->period_number === $record->current_period ? 'ring-1 ring-amber-500/40' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>Round #{{ $period->period_number }}</span>
                                    @if ($period->period_number === 1)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-normal">Organizer</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $period->formatted_due_date_bs }}
                                    @if ($period->due_date)
                                        ({{ $period->due_date->format('M d, Y') }})
                                    @endif
                                </div>
                            </div>
                            <span @class([
                                'px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0',
                                'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $period->status === 'completed',
                                'bg-amber-500/10 text-amber-600 dark:text-amber-400' => $period->status === 'in_progress',
                                'bg-slate-100 text-slate-500 dark:bg-slate-800' => ! in_array($period->status, ['completed', 'in_progress'], true),
                            ])>
                                {{ ucwords(str_replace('_', ' ', $period->status)) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <span class="text-slate-400">Winner:</span>
                                <div class="font-medium text-slate-800 dark:text-slate-200 truncate">
                                    @if ($period->winnerUser)
                                        {{ $period->winnerUser->name }} (Seat #{{ $period->winningSeat?->seat_number }})
                                    @elseif ($period->period_number === 1)
                                        {{ $record->organizer?->name ?? 'Organizer' }} (Seat #1)
                                    @else
                                        Not drawn yet
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400">Net Payout:</span>
                                <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $period->status === 'completed' ? 'Rs. ' . number_format((float) $period->net_payout_amount, 2) : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800" @click.stop>
                            <button type="button" wire:click="openPeriodDetails({{ $period->id }})"
                                class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200">
                                View
                            </button>
                            @if ($period->status !== 'completed' && $period->period_number !== 1 && \App\Filament\Resources\SavingsSchemes\SavingsSchemeResource::canShield('ManageSeats'))
                                <button type="button" wire:click="openReserveModal({{ $period->id }})"
                                    class="px-2.5 py-1 text-xs font-medium rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                    Reserve
                                </button>
                            @endif
                            <button type="button" wire:click="openValidateRound({{ $period->id }})"
                                class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $period->is_validated ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400' }}">
                                {{ $period->is_validated ? '✓ Validated' : '⚖️ Validate' }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Table View --}}
            <div class="sp-desktop-only overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-3 whitespace-nowrap">Round</th>
                            <th class="px-3 py-3 whitespace-nowrap">Due Date (BS)</th>
                            <th class="px-3 py-3 whitespace-nowrap">Winner</th>
                            <th class="px-3 py-3 whitespace-nowrap">Reservation</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Gross Pool</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Maslanda</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap font-bold text-slate-900 dark:text-white">Net Payout</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">Paid / Bal</th>
                            <th class="px-3 py-3 text-center whitespace-nowrap">Status</th>
                            <th class="px-3 py-3 text-center whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($record->periods as $period)
                            <tr wire:click="openPeriodDetails({{ $period->id }})"
                                class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/60 transition group {{ $period->period_number === $record->current_period ? 'bg-amber-500/5' : '' }}">
                                <td class="px-3 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                    Round #{{ $period->period_number }}
                                    @if ($period->period_number === 1)
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-normal">Organizer</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-900 dark:text-white">
                                        {{ $period->formatted_due_date_bs }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $period->due_date ? $period->due_date->format('M d, Y') . ' AD' : '-' }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 font-medium whitespace-nowrap">
                                    @if ($period->winnerUser)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-amber-500">🏆</span>
                                            <span class="text-slate-900 dark:text-white">{{ $period->winnerUser->name }}</span>
                                            <span class="text-xs text-slate-400">(Seat #{{ $period->winningSeat?->seat_number }})</span>
                                        </div>
                                    @elseif ($period->period_number === 1)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-amber-500">🏆</span>
                                            <span class="text-slate-900 dark:text-white">{{ $record->organizer?->name ?? 'Organizer' }}</span>
                                            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">(Seat #1)</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Not drawn yet</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @php
                                        $approvedRes = $period->reservations->firstWhere('status', 'approved');
                                        $pendingResCount = $period->reservations->where('status', 'pending')->count();
                                    @endphp

                                    @if ($approvedRes)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-500/10 text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                            <span>🔖</span>
                                            <span>Seat #{{ $approvedRes->seat?->seat_number ?? '?' }}: {{ $approvedRes->user?->name }}</span>
                                        </span>
                                    @elseif ($pendingResCount > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                            <span>⏳</span>
                                            <span>{{ $pendingResCount }} Pending</span>
                                        </span>
                                    @elseif ($period->period_number === 1)
                                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Organizer</span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Open</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right font-mono whitespace-nowrap">
                                    @if ($period->status === 'completed')
                                        Rs. {{ number_format((float) $period->gross_pool_amount, 2) }}
                                    @elseif ($period->period_number === 1)
                                        <span class="text-slate-500">Rs. {{ number_format((float) ($record->total_seats * $record->installment_amount), 2) }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-rose-500 whitespace-nowrap">
                                    @if ($period->status === 'completed')
                                        -Rs. {{ number_format((float) $period->khaja_kharcha_deducted, 2) }}
                                    @elseif ($period->period_number === 1)
                                        <span class="text-slate-400">Rs. 0.00</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    @if ($period->status === 'completed')
                                        Rs. {{ number_format((float) $period->net_payout_amount, 2) }}
                                    @elseif ($period->period_number === 1)
                                        <span class="text-emerald-600/80 dark:text-emerald-400/80">Rs. {{ number_format((float) (($record->total_seats - 1) * $record->installment_amount), 2) }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    @if ($period->status === 'completed')
                                        <div class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">
                                            Rs. {{ number_format((float) $period->paid_amount, 2) }}
                                        </div>
                                        @if ($period->payout_method)
                                            <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                {{ $period->payout_method }}
                                            </span>
                                        @endif
                                        @if ($period->winner_balance_remaining > 0)
                                            <div class="text-[10px] text-rose-500 font-semibold mt-0.5">
                                                Bal: Rs. {{ number_format($period->winner_balance_remaining, 2) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1">
                                        @if ($period->is_validated)
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                                                <span>✓</span> Validated
                                            </span>
                                        @endif
                                        @if ($period->status === 'completed')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Completed</span>
                                        @elseif ($period->status === 'in_progress')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 animate-pulse">In Progress</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">Upcoming</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5" @click.stop>
                                        <button type="button" wire:click="openPeriodDetails({{ $period->id }})"
                                            class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                                            View
                                        </button>
                                        @if ($period->period_number > 1 && $period->status !== 'completed')
                                            <button type="button" wire:click="openReserveModal({{ $period->id }})"
                                                class="px-2.5 py-1 text-xs font-medium rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 transition flex items-center gap-1"
                                                title="Reserve Round #{{ $period->period_number }}">
                                                <span>🔖</span> Reserve
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openValidateRound({{ $period->id }})"
                                            class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $period->is_validated ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/25' : 'bg-blue-500/10 hover:bg-blue-500/20 text-blue-700 dark:text-blue-300' }} transition flex items-center gap-1"
                                            title="{{ $period->is_validated ? 'Round Validated - Click to review' : 'Validate Round' }}">
                                            <span>{{ $period->is_validated ? '✓' : '⚖️' }}</span> {{ $period->is_validated ? 'Validated' : 'Validate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: Seats Roster --}}
        <div x-show="activeTab === 'seats'" class="space-y-3">
            @php
                $rosterCards = collect();
                $assignedByUser = $record->seats->whereNotNull('user_id')->groupBy('user_id');
                foreach ($record->seats as $seat) {
                    if (! $seat->user_id) {
                        $rosterCards->push(collect([$seat]));
                    } elseif ($assignedByUser->has($seat->user_id)) {
                        $rosterCards->push($assignedByUser->pull($seat->user_id));
                    }
                }
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($rosterCards as $userSeats)
                    @php
                        $primarySeat = $userSeats->first();
                        $member = $primarySeat->user;
                        $seatCount = $userSeats->count();
                        $seatLabels = $userSeats->map(fn ($s) => '#' . $s->seat_number)->implode(', ');
                        $hasOrganizerSeat = $userSeats->contains(fn ($s) => $s->seat_number === 1);
                        $wonPeriods = $userSeats->pluck('won_at_period')->filter()->values();
                        $hasInterest = $userSeats->contains(fn ($s) => $s->seat_number !== 1 && $s->won_at_period !== null);
                        $futureDuesTotal = $userSeats->sum(function ($s) use ($record) {
                            if ($s->seat_number === 1) {
                                return (float) $record->installment_amount;
                            }

                            return $s->won_at_period
                                ? (float) ($record->installment_amount + $record->interest_amount)
                                : (float) $record->installment_amount;
                        });
                    @endphp
                    <div class="p-3.5 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 truncate">
                                {{ $seatCount > 1 ? "Seats {$seatLabels}" : "Seat {$seatLabels}" }}
                            </span>
                            @if ($hasOrganizerSeat)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 shrink-0">Organizer</span>
                            @elseif ($wonPeriods->isNotEmpty())
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 shrink-0">
                                    Won Rd #{{ $wonPeriods->implode(', #') }}
                                </span>
                            @elseif ($member)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 shrink-0">
                                    {{ $seatCount > 1 ? "{$seatCount} Seats" : 'Assigned' }}
                                </span>
                            @else
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 shrink-0">Available</span>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $member ? $member->name : 'Unassigned' }}
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                @if ($member)
                                    {{ $member->username ? '@' . $member->username : 'Member' }}
                                @else
                                    Open for allocation
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                            <span>Future dues:</span>
                            @if ($hasInterest)
                                <span class="font-medium text-amber-600 dark:text-amber-400">+Interest: Rs. {{ number_format($futureDuesTotal) }}</span>
                            @else
                                <span class="font-medium">Rs. {{ number_format($futureDuesTotal) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 3: Installment Collections --}}
        <div x-show="activeTab === 'installments'" class="space-y-4">
            {{-- Period Picker Bar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 p-2 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500 px-2">Select Period:</span>
                    @foreach ($record->periods as $p)
                        <button type="button" wire:click="setSelectedPeriod({{ $p->id }})"
                            class="px-2.5 py-1 text-xs rounded-lg font-medium transition {{ $selectedPeriodId === $p->id ? 'bg-amber-500 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100' }}">
                            Period #{{ $p->period_number }} ({{ $p->formatted_due_date_bs }})
                        </button>
                    @endforeach
                </div>

                @if ($selectedPeriodId)
                    <button type="button" wire:click="refreshPeriodInstallments({{ $selectedPeriodId }})" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 ml-auto"
                        title="Reload missing installment records for this round">
                        <svg wire:loading.remove wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg wire:loading wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Refresh Records</span>
                    </button>
                @endif
            </div>

            {{-- Installments Table --}}
            @php
                $activePeriod = $record->periods->firstWhere('id', $selectedPeriodId) ?? $record->periods->first();
                $installments = $activePeriod ? $activePeriod->installments()->with(['seat.user', 'user'])->get() : collect();
                $groupedInstallments = $installments->groupBy('user_id');
                $activePeriodWinnerUserId = $activePeriod?->winner_user_id
                    ?? ($activePeriod?->period_number === 1 ? $record->organizer_id : null)
                    ?? $activePeriod?->reservations->firstWhere('status', 'approved')?->user_id;
            @endphp

            {{-- Card Grid View for Installments --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse ($groupedInstallments as $userId => $userInstallments)
                    @php
                        $firstInst = $userInstallments->first();
                        $member = $firstInst?->user;
                        $isRoundWinner = $activePeriodWinnerUserId !== null && (int) $userId === (int) $activePeriodWinnerUserId;
                        $seatNumbers = $userInstallments->map(fn ($i) => '#' . ($i->seat?->seat_number ?? '?'))->implode(', ');
                        $hasOrgSeat = $userInstallments->contains(fn ($i) => $i->seat?->seat_number === 1);
                        $baseSum = (float) $userInstallments->sum('base_amount');
                        $interestSum = (float) $userInstallments->sum('interest_amount');
                        $totalDueSum = (float) $userInstallments->sum('total_due');
                        $paidSum = (float) $userInstallments->sum(fn ($i) => in_array($i->status, ['paid', 'auto_settled_winner'], true) ? ((float) $i->paid_amount > 0 ? (float) $i->paid_amount : (float) $i->total_due) : (float) $i->paid_amount);
                        $remainingSum = max(0.0, $totalDueSum - $paidSum);
                        $allAutoSettled = $userInstallments->every(fn ($i) => $i->status === 'auto_settled_winner');
                        $allPaid = $userInstallments->every(fn ($i) => $i->status === 'paid') || ($remainingSum <= 0 && ! $allAutoSettled);
                        $isPartial = ! $allPaid && ! $allAutoSettled && $paidSum > 0;
                        $methods = $userInstallments->pluck('payment_method')->filter()->unique()->implode(', ');
                    @endphp
                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        Seat {{ $seatNumbers }}
                                    </span>
                                    @if ($hasOrgSeat)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-semibold">Org</span>
                                    @endif
                                </div>
                                <div class="mt-2 font-bold text-slate-900 dark:text-white truncate">{{ $member?->name }}</div>
                                <div class="text-xs text-slate-400 truncate">
                                    @if (\Illuminate\Support\Facades\Auth::id() === $record->organizer_id)
                                        {{ $member?->primary_contact_number ?: ($member?->username ? '@' . $member->username : '') }}
                                    @else
                                        {{ $member?->username ? '@' . $member->username : '' }}
                                    @endif
                                </div>
                            </div>

                            @if ($allPaid)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">Paid</span>
                            @elseif ($allAutoSettled)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0">Auto-Settled</span>
                            @elseif ($isPartial)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-600 dark:text-sky-400 shrink-0">Partial</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 shrink-0">Pending</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <span class="text-slate-400">Base + Interest:</span>
                                <div class="font-mono text-slate-700 dark:text-slate-300">
                                    Rs. {{ number_format($baseSum, 2) }}
                                    @if ($interestSum > 0)
                                        <span class="text-amber-600 dark:text-amber-400">(+{{ number_format($interestSum) }})</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400">Total Due:</span>
                                <div class="font-mono font-bold text-slate-900 dark:text-white">Rs. {{ number_format($totalDueSum, 2) }}</div>
                                @if ($isPartial)
                                    <div class="text-[11px] text-sky-600 dark:text-sky-400">
                                        Paid: Rs. {{ number_format($paidSum, 2) }} · Bal: Rs. {{ number_format($remainingSum, 2) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div>
                                @if ($methods)
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                        {{ $methods }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Unpaid</span>
                                @endif
                            </div>
                            <div>
                                @if ($isRoundWinner)
                                    <span class="text-xs text-blue-500 font-medium">Winner (Auto-Deducted)</span>
                                @elseif ($remainingSum > 0 && ! $allAutoSettled && \App\Filament\Resources\SavingsSchemes\SavingsSchemeResource::canShield('ManageInstallments'))
                                    <button type="button" wire:click="promptUserInstallmentPayment({{ $userId }}, {{ $selectedPeriodId }})"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">
                                        {{ $isPartial ? 'Add Settlement' : 'Record Payment' }}
                                    </button>
                                @elseif ($remainingSum > 0 && ! $allAutoSettled)
                                    <span class="text-xs text-amber-500 italic">{{ $isPartial ? 'Partial' : 'Pending' }}</span>
                                @else
                                    <span class="text-xs text-slate-400">Settled</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-6 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-center text-sm text-slate-400">
                        No installments generated for this period yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL 1: Dedicated View Round Details Modal --}}
    @if ($viewPeriodId)
        @php
            $viewPeriod = $record->periods->firstWhere('id', $viewPeriodId);
        @endphp
        @if ($viewPeriod)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[92vh] overflow-y-auto flex flex-col">
                    {{-- Modal Header --}}
                    <div class="sp-round-header flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800 sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur z-10">
                        <div class="flex items-start justify-between gap-2 w-full sm:w-auto">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                        Round #{{ $viewPeriod->period_number }} Summary
                                    </h3>
                                    @if ($viewPeriod->status === 'completed')
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Completed</span>
                                    @elseif ($viewPeriod->status === 'in_progress')
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">In Progress</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">Upcoming</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Due Date: <strong class="text-slate-800 dark:text-slate-200">{{ $viewPeriod->formatted_due_date_bs }}</strong>
                                    ({{ $viewPeriod->due_date ? $viewPeriod->due_date->format('M d, Y') . ' AD' : '-' }})
                                </div>
                            </div>
                            <button type="button" wire:click="closePeriodDetails"
                                class="sp-mobile-only p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="flex items-center flex-wrap gap-2">
                            <button type="button" wire:click="refreshPeriodInstallments({{ $viewPeriod->id }})" wire:loading.attr="disabled"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1.5 shadow-sm border border-slate-200 dark:border-slate-700"
                                title="Refresh and load any missing installment records for Round #{{ $viewPeriod->period_number }}">
                                <svg wire:loading.remove wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <svg wire:loading wire:target="refreshPeriodInstallments" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Refresh</span>
                            </button>

                            @if ($viewPeriod->status !== 'completed' && \App\Filament\Resources\SavingsSchemes\SavingsSchemeResource::canShield('DeclareWinner'))
                                <button type="button" wire:click="openDeclareWinnerModal({{ $viewPeriod->id }})"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition flex items-center gap-1.5 shadow-sm">
                                    <span>🏆</span> Choose Winner
                                </button>
                            @endif

                            @if ($viewPeriod->period_number > 1 && $viewPeriod->status !== 'completed')
                                <button type="button" wire:click="openReserveModal({{ $viewPeriod->id }})"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition flex items-center gap-1.5 shadow-sm">
                                    <span>🔖</span> Reserve Round
                                </button>
                            @endif

                            <button type="button" wire:click="openValidateRound({{ $viewPeriod->id }})"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ $viewPeriod->is_validated ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-blue-600 hover:bg-blue-500 text-white' }} transition flex items-center gap-1.5 shadow-sm">
                                <span>{{ $viewPeriod->is_validated ? '✓' : '⚖️' }}</span> {{ $viewPeriod->is_validated ? 'Validated' : 'Validate Round' }}
                            </button>

                            <button type="button" wire:click="closePeriodDetails"
                                class="sp-desktop-only p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Content --}}
                    <div class="p-4 sm:p-6 space-y-5">
                        @if ($viewPeriod->is_validated)
                            <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-lg">✓</span>
                                    <div>
                                        <div class="font-bold text-sm">Round Officially Reconciled & Validated</div>
                                        <div class="text-emerald-700/80 dark:text-emerald-300/80 mt-0.5">
                                            @if ($viewPeriod->validator)
                                                <span>Validated by <strong>{{ $viewPeriod->validator->name }}</strong></span>
                                            @endif
                                            @if ($viewPeriod->validated_at)
                                                <span> on {{ $viewPeriod->validated_at->format('M d, Y · h:i A') }}</span>
                                            @endif
                                        </div>
                                        @if ($viewPeriod->validation_notes)
                                            <div class="mt-1 text-slate-600 dark:text-slate-400 italic">"{{ $viewPeriod->validation_notes }}"</div>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" wire:click="toggleRevokeValidation({{ $viewPeriod->id }})"
                                    class="px-2.5 py-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 rounded-md transition">
                                    Revoke
                                </button>
                            </div>
                        @endif
                        @php
                            $isRoundCompleted = ($viewPeriod->status === 'completed' && $viewPeriod->winning_seat_id);
                            $grossPoolDisplay = $isRoundCompleted
                                ? (float) $viewPeriod->gross_pool_amount
                                : ((float) $viewPeriod->installments->sum('total_due') ?: (float) ($record->total_seats * $record->installment_amount));
                            $maslandaDeduction = (float) ($viewPeriod->period_number === 1 ? 0 : $record->khaja_kharcha);
                            
                            // Net Payout preview logic
                            if ($isRoundCompleted) {
                                $netPayoutDisplay = (float) $viewPeriod->net_payout_amount;
                            } else {
                                $reservedSeat = $viewPeriod->approvedReservation?->seat;
                                $candidateDues = $reservedSeat
                                    ? (float) $viewPeriod->installments->where('user_id', $reservedSeat->user_id)->sum('total_due')
                                    : (float) $record->installment_amount;
                                $netPayoutDisplay = max(0.0, $grossPoolDisplay - $maslandaDeduction - $candidateDues);
                            }
                        @endphp
                        {{-- 4 Metric Cards --}}
                        <div class="sp-round-summary-grid grid grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200/60 dark:border-slate-800">
                                <div class="text-xs text-slate-500">Gross Pool</div>
                                <div class="mt-1 text-lg font-bold font-mono text-slate-900 dark:text-white">
                                    Rs. {{ number_format($grossPoolDisplay, 2) }}
                                </div>
                            </div>

                            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200/60 dark:border-slate-800">
                                <div class="text-xs text-rose-500">Maslanda Deposit</div>
                                <div class="mt-1 text-lg font-bold font-mono text-rose-600 dark:text-rose-400">
                                    -Rs. {{ number_format($maslandaDeduction, 2) }}
                                </div>
                            </div>

                            <div class="p-3.5 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {{ $isRoundCompleted ? 'Net Payout to Winner' : 'Estimated Net Payout' }}
                                    </div>
                                    @if (! $isRoundCompleted)
                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">Est.</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400">
                                    Rs. {{ number_format($netPayoutDisplay, 2) }}
                                </div>
                            </div>

                            <div class="p-3.5 {{ ($isRoundCompleted && $viewPeriod->winner_balance_remaining > 0) ? 'bg-amber-500/10 border-amber-500/20' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200/60 dark:border-slate-800' }} rounded-xl border">
                                <div class="text-xs {{ ($isRoundCompleted && $viewPeriod->winner_balance_remaining > 0) ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-slate-500' }}">Winner Balance Due</div>
                                <div class="mt-1 text-lg font-bold font-mono {{ ($isRoundCompleted && $viewPeriod->winner_balance_remaining > 0) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">
                                    @if ($isRoundCompleted)
                                        Rs. {{ number_format((float) $viewPeriod->winner_balance_remaining, 2) }}
                                    @else
                                        <span class="text-xs font-normal text-slate-400 italic">Pending Winner</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Winner Settlement Card --}}
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🏆</span>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            @if ($viewPeriod->winnerUser)
                                                {{ $viewPeriod->winnerUser->name }} (Seat #{{ $viewPeriod->winningSeat?->seat_number }})
                                            @elseif ($viewPeriod->period_number === 1)
                                                {{ $record->organizer?->name ?? 'Organizer' }} (Seat #1 · Reserved)
                                            @else
                                                <span class="text-slate-400 italic">Winner Not Declared Yet</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            @if ($viewPeriod->winnerUser)
                                                @if (\Illuminate\Support\Facades\Auth::id() === $record->organizer_id && $viewPeriod->winnerUser->primary_contact_number)
                                                    Phone: {{ $viewPeriod->winnerUser->primary_contact_number }}
                                                @elseif ($viewPeriod->winnerUser->username)
                                                    {{ '@' . $viewPeriod->winnerUser->username }}
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="text-xs text-slate-500">Settlement Status:</div>
                                    @if ($viewPeriod->payout_status === 'paid')
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Fully Paid</span>
                                    @elseif ($viewPeriod->paid_amount > 0)
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">Partially Paid (Rs. {{ number_format((float) $viewPeriod->paid_amount, 2) }})</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800">Pending</span>
                                    @endif
                                </div>
                            </div>

                            @if ($viewPeriod->payout_method || $viewPeriod->payout_reference || $viewPeriod->paid_at)
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700/60 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                    <div><span class="text-slate-500">Payout Method:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $viewPeriod->payout_method ?? 'Cash' }}</strong></div>
                                    <div><span class="text-slate-500">Reference / ID:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $viewPeriod->payout_reference ?? 'None' }}</strong></div>
                                    <div><span class="text-slate-500">Paid Date:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $viewPeriod->paid_at ? \App\Services\NepaliDateService::formatBs($viewPeriod->paid_at) . ' (' . $viewPeriod->paid_at->format('M d, Y') . ')' : '-' }}</strong></div>
                                </div>
                            @endif
                        </div>

                        {{-- Installments Table for this Period --}}
                        <div class="space-y-2">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white flex flex-wrap items-center justify-between gap-1">
                                <span>Round #{{ $viewPeriod->period_number }} Member Installments Ledger</span>
                                <span class="text-xs font-normal text-slate-500">
                                    Collected: <strong>Rs. {{ number_format($viewPeriod->total_collected_amount, 2) }}</strong>
                                </span>
                            </h4>

                            @php
                                $viewGroupedInstallments = $viewPeriod->installments->groupBy('user_id');
                                $viewPeriodWinnerUserId = $viewPeriod->winner_user_id
                                    ?? ($viewPeriod->period_number === 1 ? $record->organizer_id : null)
                                    ?? $viewPeriod->reservations->firstWhere('status', 'approved')?->user_id;
                            @endphp

                            {{-- Mobile Card View inside Modal 1 --}}
                            <div class="sp-mobile-only space-y-2.5">
                                @forelse ($viewGroupedInstallments as $userId => $userInsts)
                                    @php
                                        $firstInst = $userInsts->first();
                                        $member = $firstInst?->user;
                                        $isViewWinner = $viewPeriodWinnerUserId !== null && (int) $userId === (int) $viewPeriodWinnerUserId;
                                        $seatNumbers = $userInsts->map(fn ($i) => '#' . ($i->seat?->seat_number ?? '?'))->implode(', ');
                                        $totalDueSum = (float) $userInsts->sum('total_due');
                                        $paidSum = (float) $userInsts->sum(fn ($i) => in_array($i->status, ['paid', 'auto_settled_winner'], true) ? ((float) $i->paid_amount > 0 ? (float) $i->paid_amount : (float) $i->total_due) : (float) $i->paid_amount);
                                        $remainingSum = max(0.0, $totalDueSum - $paidSum);
                                        $allAutoSettled = $userInsts->every(fn ($i) => $i->status === 'auto_settled_winner');
                                        $allPaid = $userInsts->every(fn ($i) => $i->status === 'paid') || ($remainingSum <= 0 && ! $allAutoSettled);
                                        $isPartial = ! $allPaid && ! $allAutoSettled && $paidSum > 0;
                                    @endphp
                                    <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2 text-xs">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-white">{{ $member?->name ?? 'Unassigned' }}</div>
                                                <div class="text-[11px] text-slate-500">Seat {{ $seatNumbers }}</div>
                                            </div>
                                            @if ($allPaid)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-600">Paid</span>
                                            @elseif ($allAutoSettled)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/10 text-blue-600">Auto-Settled</span>
                                            @elseif ($isPartial)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-500/10 text-sky-600">Partial</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-600">Pending</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between pt-2 border-t border-slate-200/70 dark:border-slate-700/60">
                                            <div class="font-mono font-bold text-slate-900 dark:text-white">
                                                Rs. {{ number_format($totalDueSum, 2) }}
                                                @if ($isPartial)
                                                    <span class="font-normal text-sky-500 ml-1">(Bal: {{ number_format($remainingSum, 2) }})</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if ($isViewWinner)
                                                    <span class="text-blue-500 text-[10px] font-medium">Winner (Auto-Deducted)</span>
                                                @elseif ($remainingSum > 0 && ! $allAutoSettled && \App\Filament\Resources\SavingsSchemes\SavingsSchemeResource::canShield('ManageInstallments'))
                                                    <button type="button" wire:click="promptUserInstallmentPayment({{ $userId }}, {{ $viewPeriod->id }})"
                                                        class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500">
                                                        {{ $isPartial ? '+ Settle' : 'Settle' }}
                                                    </button>
                                                @else
                                                    <span class="text-slate-400 text-[10px]">Settled</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-center text-xs text-slate-400 italic">
                                        No installments generated.
                                    </div>
                                @endforelse
                            </div>

                            <div class="sp-desktop-only overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                                    <thead class="bg-slate-50 dark:bg-slate-800/50 font-semibold uppercase text-slate-500 border-b border-slate-200 dark:border-slate-800">
                                        <tr>
                                            <th class="px-3 py-2.5">Seat</th>
                                            <th class="px-3 py-2.5">Member</th>
                                            <th class="px-3 py-2.5 text-right">Base</th>
                                            <th class="px-3 py-2.5 text-right">Interest</th>
                                            <th class="px-3 py-2.5 text-right font-bold text-slate-900 dark:text-white">Total Due</th>
                                            <th class="px-3 py-2.5 text-center">Status</th>
                                            <th class="px-3 py-2.5 text-center">Method</th>
                                            <th class="px-3 py-2.5 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @php
                                            $viewGroupedInstallments = $viewPeriod->installments->groupBy('user_id');
                                            $viewPeriodWinnerUserId = $viewPeriod->winner_user_id
                                                ?? ($viewPeriod->period_number === 1 ? $record->organizer_id : null)
                                                ?? $viewPeriod->reservations->firstWhere('status', 'approved')?->user_id;
                                        @endphp
                                        @forelse ($viewGroupedInstallments as $userId => $userInsts)
                                            @php
                                                $firstInst = $userInsts->first();
                                                $member = $firstInst?->user;
                                                $isViewWinner = $viewPeriodWinnerUserId !== null && (int) $userId === (int) $viewPeriodWinnerUserId;
                                                $seatNumbers = $userInsts->map(fn ($i) => '#' . ($i->seat?->seat_number ?? '?'))->implode(', ');
                                                $baseSum = (float) $userInsts->sum('base_amount');
                                                $interestSum = (float) $userInsts->sum('interest_amount');
                                                $totalDueSum = (float) $userInsts->sum('total_due');
                                                $paidSum = (float) $userInsts->sum(fn ($i) => in_array($i->status, ['paid', 'auto_settled_winner'], true) ? ((float) $i->paid_amount > 0 ? (float) $i->paid_amount : (float) $i->total_due) : (float) $i->paid_amount);
                                                $remainingSum = max(0.0, $totalDueSum - $paidSum);
                                                $allAutoSettled = $userInsts->every(fn ($i) => $i->status === 'auto_settled_winner');
                                                $allPaid = $userInsts->every(fn ($i) => $i->status === 'paid') || ($remainingSum <= 0 && ! $allAutoSettled);
                                                $isPartial = ! $allPaid && ! $allAutoSettled && $paidSum > 0;
                                                $methods = $userInsts->pluck('payment_method')->filter()->unique()->implode(', ');
                                            @endphp
                                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                                <td class="px-3 py-2.5 font-semibold text-slate-900 dark:text-white">
                                                    Seat {{ $seatNumbers }}
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    {{ $member?->name ?? 'Unassigned' }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right font-mono">
                                                    Rs. {{ number_format($baseSum, 2) }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right font-mono text-amber-600">
                                                    {{ $interestSum > 0 ? '+Rs. ' . number_format($interestSum, 2) : '-' }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                                    <div>Rs. {{ number_format($totalDueSum, 2) }}</div>
                                                    @if ($isPartial)
                                                        <div class="text-[10px] font-normal text-sky-600 dark:text-sky-400">
                                                            Paid: Rs. {{ number_format($paidSum, 2) }} · Bal: Rs. {{ number_format($remainingSum, 2) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    @if ($allPaid)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-600">Paid</span>
                                                    @elseif ($allAutoSettled)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/10 text-blue-600">Auto-Settled</span>
                                                    @elseif ($isPartial)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-500/10 text-sky-600">Partial</span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-600">Pending</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 text-center">
                                                    @if ($methods)
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                                            {{ $methods }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 text-right">
                                                    @if ($isViewWinner)
                                                        <span class="text-blue-500 text-[10px] font-medium">Winner (Auto-Deducted)</span>
                                                    @elseif ($remainingSum > 0 && ! $allAutoSettled && \App\Filament\Resources\SavingsSchemes\SavingsSchemeResource::canShield('ManageInstallments'))
                                                        <button type="button" wire:click="promptUserInstallmentPayment({{ $userId }}, {{ $viewPeriod->id }})"
                                                            class="px-2 py-0.5 text-[11px] font-semibold rounded bg-emerald-600 text-white hover:bg-emerald-500">
                                                            {{ $isPartial ? '+ Settle' : 'Settle' }}
                                                        </button>
                                                    @else
                                                        <span class="text-slate-400 text-[10px]">Settled</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="px-3 py-4 text-center text-slate-400 italic">No installments generated.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end bg-slate-50/50 dark:bg-slate-800/30">
                        <button type="button" wire:click="closePeriodDetails"
                            class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">
                            Close Summary
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- MODAL 2: Record Installment Settlement with Partial/Full & Date Modal --}}
    @if ($selectedInstallmentUserId && $selectedInstallmentPeriodId)
        @php
            $targetUser = \App\Models\User::find($selectedInstallmentUserId);
            $userPeriodInsts = \App\Models\SchemeInstallment::with('seat')
                ->where('scheme_period_id', $selectedInstallmentPeriodId)
                ->where('user_id', $selectedInstallmentUserId)
                ->orderBy('scheme_seat_id')
                ->get();
            $modalSeats = $userPeriodInsts->map(fn ($i) => '#' . ($i->seat?->seat_number ?? '?'))->implode(', ');
            $modalTotalDue = (float) $userPeriodInsts->sum('total_due');
            $modalAlreadyPaid = (float) $userPeriodInsts->sum(fn ($i) => in_array($i->status, ['paid', 'auto_settled_winner'], true) ? ((float) $i->paid_amount > 0 ? (float) $i->paid_amount : (float) $i->total_due) : (float) $i->paid_amount);
            $modalRemaining = max(0.0, $modalTotalDue - $modalAlreadyPaid);
        @endphp
        @if ($targetUser && $userPeriodInsts->isNotEmpty())
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="sp-custom-modal bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Member Settlement</h3>
                            <div class="text-xs text-slate-500">
                                Seat(s) {{ $modalSeats }} — {{ $targetUser->name }}
                            </div>
                        </div>
                        <button type="button" wire:click="cancelInstallmentPrompt" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Total Due ({{ $userPeriodInsts->count() }} {{ $userPeriodInsts->count() === 1 ? 'seat' : 'seats' }}):</span>
                            <strong class="font-mono text-slate-900 dark:text-white">Rs. {{ number_format($modalTotalDue, 2) }}</strong>
                        </div>
                        @if ($modalAlreadyPaid > 0)
                            <div class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                <span>Already Paid:</span>
                                <span class="font-mono font-semibold">Rs. {{ number_format($modalAlreadyPaid, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between pt-1 border-t border-amber-500/20">
                            <span class="text-slate-700 dark:text-slate-200 font-semibold">Remaining Balance:</span>
                            <strong class="font-mono text-base text-amber-600 dark:text-amber-400">Rs. {{ number_format($modalRemaining, 2) }}</strong>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Settlement Amount (Rs.) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" max="{{ $modalRemaining }}" wire:model="installmentPaymentAmount"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Settlement Date (Nepali BS) <span class="text-rose-500">*</span>
                                </label>
                                @if ($installmentPaidAtBsString)
                                    <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400">
                                        🗓️ {{ \App\Services\NepaliDateService::formatBs($installmentPaidAtBsString) }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <select wire:model.live="installmentPaidBsYear"
                                        class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                        @foreach (\App\Services\NepaliDateService::getNepaliYears() as $yVal => $yLabel)
                                            <option value="{{ $yVal }}">{{ $yLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <select wire:model.live="installmentPaidBsMonth"
                                        class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                        @foreach (\App\Services\NepaliDateService::getNepaliMonths() as $mVal => $mLabel)
                                            <option value="{{ $mVal }}">{{ $mLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <select wire:model.live="installmentPaidBsDay"
                                        class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                        @foreach (\App\Services\NepaliDateService::getNepaliDays($installmentPaidBsMonth, $installmentPaidBsYear) as $dVal => $dLabel)
                                            <option value="{{ $dVal }}">{{ $dLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-1 text-[11px] text-slate-400">
                                English (AD): {{ $installmentPaidAt ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Payment Method <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="installmentPaymentMethod"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                <option value="Cash">Cash</option>
                                <option value="eSewa">eSewa (Digital Wallet)</option>
                                <option value="Khalti">Khalti (Digital Wallet)</option>
                                <option value="Bank Transfer">Bank Transfer (A/C)</option>
                                <option value="Other">Other / Cheque</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Transaction Reference / ID (Optional)
                            </label>
                            <input type="text" wire:model="installmentPaymentReference" placeholder="e.g. eSewa Txn ID, Bank UTR #"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Notes (Optional)
                            </label>
                            <textarea wire:model="installmentNotes" rows="2" placeholder="Payment notes"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" wire:click="cancelInstallmentPrompt"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="button" wire:click="confirmMarkInstallmentPaid"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white transition">
                            Confirm & Mark Paid
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- MODAL 3: Direct Reserve Round Modal --}}
    @if ($reservingPeriodId)
        @php
            $resPeriod = $record->periods->firstWhere('id', $reservingPeriodId);
            $availableSeats = $record->seats()
                ->where('seat_number', '!=', 1)
                ->whereNull('won_at_period')
                ->with('user')
                ->get();
        @endphp
        @if ($resPeriod)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="sp-custom-modal bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>🔖</span> Reserve Round #{{ $resPeriod->period_number }}
                            </h3>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Due Date: <strong>{{ $resPeriod->formatted_due_date_bs }}</strong>
                            </div>
                        </div>
                        <button type="button" wire:click="closeReserveModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Member Seat to Reserve <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="reservingSeatId"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                <option value="">-- Select Member Seat --</option>
                                @foreach ($availableSeats as $s)
                                    <option value="{{ $s->id }}">
                                        Seat #{{ $s->seat_number }} — {{ $s->user?->name ?? 'Unassigned' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Reason / Occasion
                            </label>
                            <textarea wire:model="reservingReason" rows="2" placeholder="e.g. Wedding in Magh, House renovation, Business expense"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Reservation Status
                            </label>
                            <select wire:model="reservingStatus"
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                <option value="approved">Approved (Lock seat as reserved)</option>
                                <option value="pending">Pending Application</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" wire:click="closeReserveModal"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="button" wire:click="confirmReserve"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition flex items-center gap-1">
                            <span>🔖</span> Save Reservation
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- MODAL 3.5: Declare Winner Modal --}}
    @if ($declaringWinnerPeriodId)
        @php
            $decPeriod = $record->periods->firstWhere('id', $declaringWinnerPeriodId);
            $eligibleSeats = $record->seats->filter(function ($s) use ($decPeriod) {
                // Must be assigned to a user
                if (! $s->user_id) {
                    return false;
                }
                // If period 1, only seat 1
                if ($decPeriod?->period_number === 1) {
                    return $s->seat_number === 1;
                }
                // Else seat must not have won in another period
                return $s->won_at_period === null;
            });
            $selectedWinningSeat = $eligibleSeats->firstWhere('id', $declaringWinnerSeatId);
            $previewGross = (float) ($decPeriod?->installments->sum('total_due') ?: ($record->total_seats * $record->installment_amount));
            $previewKhaja = (float) ($decPeriod?->period_number === 1 ? 0 : $record->khaja_kharcha);
            $previewDues = $selectedWinningSeat
                ? (float) ($decPeriod?->installments->where('user_id', $selectedWinningSeat->user_id)->sum('total_due') ?: $record->installment_amount)
                : 0.00;
            $previewNetPrize = max(0.00, $previewGross - $previewKhaja - $previewDues);
        @endphp
        @if ($decPeriod)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="sp-custom-modal bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>🏆</span> Choose Winner for Round #{{ $decPeriod->period_number }}
                            </h3>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Due Date: <strong>{{ $decPeriod->formatted_due_date_bs }}</strong>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDeclareWinnerModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
                    </div>

                    <div class="space-y-3">
                        @if ($decPeriod->period_number === 1)
                            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-700 dark:text-amber-300">
                                Round #1 is reserved for the Organizer (Seat #1 - {{ $record->organizer?->name }}).
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Select Winning Seat <span class="text-rose-500">*</span>
                                </label>
                                <select wire:model.live="declaringWinnerSeatId"
                                    class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition">
                                    <option value="">-- Choose Winning Seat --</option>
                                    @foreach ($eligibleSeats as $s)
                                        <option value="{{ $s->id }}">
                                            Seat #{{ $s->seat_number }} — {{ $s->user?->name ?? 'Unassigned' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Calculation Breakdown Preview --}}
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/60 space-y-1.5 text-xs">
                            <div class="font-semibold text-slate-700 dark:text-slate-200 pb-1 border-b border-slate-200 dark:border-slate-700">
                                Payout Breakdown Preview
                            </div>
                            <div class="flex justify-between text-slate-600 dark:text-slate-300">
                                <span>Gross Pool:</span>
                                <span class="font-mono font-medium">Rs. {{ number_format($previewGross, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-rose-600 dark:text-rose-400">
                                <span>Maslanda Deposit:</span>
                                <span class="font-mono font-medium">-Rs. {{ number_format($previewKhaja, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-amber-600 dark:text-amber-400">
                                <span>Winner Seat Dues (Auto-Settled):</span>
                                <span class="font-mono font-medium">-Rs. {{ number_format($previewDues, 2) }}</span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700 text-emerald-600 dark:text-emerald-400 font-bold">
                                <span>Net Prize to Winner:</span>
                                <span class="font-mono text-sm">Rs. {{ number_format($previewNetPrize, 2) }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Notes (Optional)
                            </label>
                            <textarea wire:model="declaringWinnerNotes" rows="2" placeholder="e.g. Draw held at community center; Seat #4 won."
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" wire:click="closeDeclareWinnerModal"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="button" wire:click="confirmDeclareWinner"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition flex items-center gap-1">
                            <span>🏆</span> Confirm Winner
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- MODAL 4: Round Audit & Validation Modal --}}
    @if ($validatingPeriodId)
        @php
            $valPeriod = $record->periods->firstWhere('id', $validatingPeriodId);
            $totalSeatsCount = $valPeriod?->installments->count() ?? 0;
            $paidSeatsCount = $valPeriod?->installments->where('status', 'paid')->count() ?? 0;
            $unpaidInstallments = $valPeriod?->installments->where('status', '!=', 'paid') ?? collect();
            $targetPool = max(0.00, (float) $valPeriod?->gross_pool_amount - (float) $valPeriod?->winner_dues_deducted);
            $collectedAmount = (float) $valPeriod?->total_collected_amount;
            $pendingCollection = max(0.00, $targetPool - $collectedAmount);
            $isWinnerDeclared = (bool) $valPeriod?->winning_seat_id;
            $winnerBalanceRemaining = (float) $valPeriod?->winner_balance_remaining;
        @endphp
        @if ($valPeriod)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="sp-custom-modal bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-5 space-y-4 max-h-[92vh] overflow-y-auto">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>⚖️</span> Audit & Validate Round #{{ $valPeriod->period_number }}
                            </h3>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Due: <strong>{{ $valPeriod->formatted_due_date_bs }}</strong>
                                ({{ $valPeriod->due_date ? $valPeriod->due_date->format('M d, Y') . ' AD' : '-' }})
                            </div>
                        </div>
                        <button type="button" wire:click="closeValidateRound" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
                    </div>

                    {{-- Validation Status Badge --}}
                    @if ($valPeriod->is_validated)
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-800 dark:text-emerald-300">
                            <div class="font-bold flex items-center gap-1.5">
                                <span>✓</span> Officially Validated
                            </div>
                            <div class="mt-0.5 text-[11px] text-emerald-700/80 dark:text-emerald-300/80">
                                @if ($valPeriod->validator)
                                    <span>Validated by <strong>{{ $valPeriod->validator->name }}</strong></span>
                                @endif
                                @if ($valPeriod->validated_at)
                                    <span> on {{ $valPeriod->validated_at->format('M d, Y · h:i A') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Checklist Section 1: Member Collections --}}
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>1️⃣</span> Member Collections Reconciliation
                            </span>
                            @if ($pendingCollection <= 0.01 && $paidSeatsCount >= $totalSeatsCount && $totalSeatsCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                    ✓ 100% Collected
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-600 dark:text-amber-400">
                                    Pending Balance
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                            <div><span class="text-slate-500">Collected:</span> <strong class="font-mono text-emerald-600 dark:text-emerald-400">Rs. {{ number_format($collectedAmount, 2) }}</strong></div>
                            <div><span class="text-slate-500">Target Pool:</span> <strong class="font-mono text-slate-800 dark:text-slate-200">Rs. {{ number_format($targetPool, 2) }}</strong></div>
                            <div><span class="text-slate-500">Seats Paid:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $paidSeatsCount }} / {{ $totalSeatsCount }}</strong></div>
                            <div><span class="text-slate-500">Pending Amount:</span> <strong class="font-mono text-rose-500">Rs. {{ number_format($pendingCollection, 2) }}</strong></div>
                        </div>

                        @if ($unpaidInstallments->count() > 0)
                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700/60 text-[11px]">
                                <div class="font-semibold text-rose-500 mb-1">Unpaid Member Seats:</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($unpaidInstallments as $ui)
                                        <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-600 dark:text-rose-400 font-medium">
                                            Seat #{{ $ui->seat?->seat_number }}: {{ $ui->user?->name ?? 'Unassigned' }} (Rs. {{ number_format((float) $ui->total_due, 0) }})
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Checklist Section 2: Winner & Prize Payout --}}
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>2️⃣</span> Winner Prize Payout Reconciliation
                            </span>
                            @if ($isWinnerDeclared && $winnerBalanceRemaining <= 0.01)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                    ✓ Payout Settled
                                </span>
                            @elseif ($isWinnerDeclared)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-600 dark:text-amber-400">
                                    Partial Payout
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700">
                                    Winner Pending
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                            <div class="col-span-2">
                                <span class="text-slate-500">Winner:</span>
                                <strong class="text-slate-900 dark:text-white">
                                    @if ($valPeriod->winnerUser)
                                        {{ $valPeriod->winnerUser->name }} (Seat #{{ $valPeriod->winningSeat?->seat_number }})
                                    @elseif ($valPeriod->period_number === 1)
                                        {{ $record->organizer?->name ?? 'Organizer' }} (Seat #1 · Reserved)
                                    @else
                                        <span class="text-slate-400 italic font-normal">Not declared yet</span>
                                    @endif
                                </strong>
                            </div>
                            <div><span class="text-slate-500">Net Prize:</span> <strong class="font-mono text-emerald-600 dark:text-emerald-400">Rs. {{ number_format((float) $valPeriod->net_payout_amount, 2) }}</strong></div>
                            <div><span class="text-slate-500">Paid to Winner:</span> <strong class="font-mono text-slate-800 dark:text-slate-200">Rs. {{ number_format((float) $valPeriod->paid_amount, 2) }}</strong></div>
                            @if ($winnerBalanceRemaining > 0)
                                <div class="col-span-2"><span class="text-slate-500">Remaining Balance:</span> <strong class="font-mono text-rose-500">Rs. {{ number_format($winnerBalanceRemaining, 2) }}</strong></div>
                            @endif
                        </div>
                    </div>

                    {{-- Validation Notes Input --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Reconciliation & Validation Notes (Optional)
                        </label>
                        <textarea wire:model="validationNotes" rows="2" placeholder="e.g. Verified bank receipts and signed vouchers by organizer."
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition"></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                        <div>
                            @if ($valPeriod->is_validated)
                                <button type="button" wire:click="toggleRevokeValidation({{ $valPeriod->id }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition">
                                    Revoke Validation
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="closeValidateRound"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                                Close
                            </button>
                            <button type="button" wire:click="confirmValidateRound"
                                class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-1.5">
                                <span>✓</span> {{ $valPeriod->is_validated ? 'Update Validation' : 'Confirm & Validate Round' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
