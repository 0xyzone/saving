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
            <div class="text-xs font-medium text-rose-500 dark:text-rose-400">Khaja Kharcha (Snacks)</div>
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
            <div class="mt-1 text-xs text-slate-500">{{ ucfirst($record->frequency) }} schedule</div>
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
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3">Due Date</th>
                            <th class="px-4 py-3">Winner</th>
                            <th class="px-4 py-3 text-right">Gross Pool</th>
                            <th class="px-4 py-3 text-right">Winner Dues</th>
                            <th class="px-4 py-3 text-right">Khaja Kharcha</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">Net Payout</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($record->periods as $period)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition {{ $period->period_number === $record->current_period ? 'bg-amber-500/5' : '' }}">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    Round #{{ $period->period_number }}
                                    @if ($period->period_number === 1)
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-normal">Organizer Round</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                    {{ $period->due_date ? $period->due_date->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    @if ($period->winnerUser)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-amber-500">🏆</span>
                                            <span class="text-slate-900 dark:text-white">{{ $period->winnerUser->name }}</span>
                                            <span class="text-xs text-slate-400">(Seat #{{ $period->winningSeat?->seat_number }})</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Not drawn yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    Rs. {{ number_format((float) $period->gross_pool_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-amber-600 dark:text-amber-400">
                                    -Rs. {{ number_format((float) $period->winner_dues_deducted, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-rose-500">
                                    -Rs. {{ number_format((float) $period->khaja_kharcha_deducted, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    Rs. {{ number_format((float) $period->net_payout_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($period->status === 'completed')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Completed</span>
                                    @elseif ($period->status === 'in_progress')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 animate-pulse">In Progress</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">Upcoming</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: Seats Roster --}}
        <div x-show="activeTab === 'seats'" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($record->seats as $seat)
                    <div class="p-3.5 bg-white border border-slate-200 dark:border-slate-800 rounded-xl dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                Seat #{{ $seat->seat_number }}
                            </span>
                            @if ($seat->seat_number === 1)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400">Organizer</span>
                            @elseif ($seat->won_at_period)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Won Rd #{{ $seat->won_at_period }}</span>
                            @elseif ($seat->user)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400">Assigned</span>
                            @else
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">Available</span>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $seat->user ? $seat->user->name : 'Unassigned' }}
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                {{ $seat->user ? $seat->user->primary_contact_number : 'Open for allocation' }}
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                            <span>Future dues:</span>
                            @if ($seat->seat_number === 1)
                                <span class="font-medium">Rs. {{ number_format((float) $record->installment_amount) }}</span>
                            @elseif ($seat->won_at_period)
                                <span class="font-medium text-amber-600 dark:text-amber-400">+Interest: Rs. {{ number_format((float) ($record->installment_amount + $record->interest_amount)) }}</span>
                            @else
                                <span class="font-medium">Rs. {{ number_format((float) $record->installment_amount) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 3: Installment Collections --}}
        <div x-show="activeTab === 'installments'" class="space-y-4">
            {{-- Period Picker Bar --}}
            <div class="flex flex-wrap items-center gap-2 p-2 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800">
                <span class="text-xs font-semibold uppercase text-slate-500 px-2">Select Period:</span>
                @foreach ($record->periods as $p)
                    <button type="button" wire:click="setSelectedPeriod({{ $p->id }})"
                        class="px-2.5 py-1 text-xs rounded-lg font-medium transition {{ $selectedPeriodId === $p->id ? 'bg-amber-500 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100' }}">
                        Period #{{ $p->period_number }}
                    </button>
                @endforeach
            </div>

            {{-- Installments Table --}}
            @php
                $activePeriod = $record->periods->firstWhere('id', $selectedPeriodId) ?? $record->periods->first();
                $installments = $activePeriod ? $activePeriod->installments()->with(['seat.user', 'user'])->get() : collect();
            @endphp

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3">Seat</th>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3 text-right">Base Amount</th>
                            <th class="px-4 py-3 text-right">Interest</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">Total Due</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($installments as $inst)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    Seat #{{ $inst->seat?->seat_number }}
                                    @if ($inst->seat?->seat_number === 1)
                                        <span class="ml-1 text-[10px] px-1 rounded bg-amber-500/20 text-amber-600">Org</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $inst->user?->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $inst->user?->primary_contact_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    Rs. {{ number_format((float) $inst->base_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-amber-600 dark:text-amber-400">
                                    {{ $inst->interest_amount > 0 ? '+Rs. ' . number_format((float) $inst->interest_amount, 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rs. {{ number_format((float) $inst->total_due, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($inst->status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Paid</span>
                                    @elseif ($inst->status === 'auto_settled_winner')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400">Auto-Settled (Winner)</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($inst->status === 'pending')
                                        <button type="button" wire:click="markInstallmentPaid({{ $inst->id }})"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-md bg-emerald-600 text-white hover:bg-emerald-500 transition">
                                            Mark Paid
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-400 italic">
                                    No installments generated for this period yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
