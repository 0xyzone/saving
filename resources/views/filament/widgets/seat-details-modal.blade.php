<div class="space-y-6">
    {{-- Scheme Summary --}}
    <div class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5 sm:grid-cols-3">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Organizer</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                {{ $seat->scheme?->organizer?->name ?? 'N/A' }}
            </p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Installment / Seat</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                Rs. {{ number_format((float) $seat->scheme?->installment_amount, 2) }}
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ ucfirst($seat->scheme?->frequency ?? 'monthly') }})</span>
            </p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Current Running Period</p>
            <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">
                Period {{ $seat->scheme?->current_period }} of {{ $seat->scheme?->total_periods }}
            </p>
            @if ($currentPeriod)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Due: {{ $currentPeriod->formatted_due_date_bs }}
                    @if ($currentPeriod->due_date)
                        ({{ $currentPeriod->due_date->format('M d, Y') }})
                    @endif
                </p>
            @endif
        </div>
    </div>

    {{-- Current Period Payment Highlight --}}
    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Current Period Payment Status</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Seat #{{ $seat->seat_number }}
                    @if ($seat->won_at_period)
                        &middot; Won at Round #{{ $seat->won_at_period }}
                    @endif
                </p>
            </div>
            @if ($currentInstallment)
                <span @class([
                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $currentInstallment->status === 'paid',
                    'bg-amber-500/10 text-amber-600 dark:text-amber-400' => $currentInstallment->status === 'pending',
                    'bg-sky-500/10 text-sky-600 dark:text-sky-400' => $currentInstallment->status === 'waived',
                ])>
                    {{ ucfirst($currentInstallment->status) }} &mdash; Rs. {{ number_format((float) $currentInstallment->total_due, 2) }}
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-gray-500/10 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
                    Not Yet Generated
                </span>
            @endif
        </div>
    </div>

    {{-- Installment History for This Seat --}}
    <div>
        <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Seat Installment Ledger</h4>
        @php
            $installments = $seat->installments->sortByDesc(fn ($inst) => $inst->period?->period_number ?? 0);
            $pendingRequestInstallmentIds = $seat->settlementRequests
                ->where('status', \App\Models\InstallmentSettlementRequest::STATUS_PENDING)
                ->pluck('scheme_installment_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        @endphp

        @if ($installments->isEmpty())
            <p class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                No installments have been generated for this seat yet.
            </p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2.5">Period</th>
                            <th class="px-4 py-2.5">Due Date (BS)</th>
                            <th class="px-4 py-2.5">Amount</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Paid On</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($installments as $inst)
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    Period #{{ $inst->period?->period_number ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                                    {{ $inst->period?->formatted_due_date_bs ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    Rs. {{ number_format((float) $inst->total_due, 2) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $inst->status === 'paid',
                                            'bg-amber-500/10 text-amber-600 dark:text-amber-400' => $inst->status === 'pending',
                                            'bg-sky-500/10 text-sky-600 dark:text-sky-400' => $inst->status === 'waived',
                                        ])>
                                            {{ ucfirst($inst->status) }}
                                        </span>
                                        @if (in_array((int) $inst->id, $pendingRequestInstallmentIds, true))
                                            <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                                Under Review
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">
                                    @if ($inst->paid_at)
                                        <div class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ \App\Services\NepaliDateService::formatBs($inst->paid_at) }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $inst->paid_at->format('M d, Y') }} AD
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Settlement Applications History --}}
    @if ($seat->settlementRequests->isNotEmpty())
        <div>
            <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Settlement Applications</h4>
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2.5">Round</th>
                            <th class="px-4 py-2.5">Payment Date (BS)</th>
                            <th class="px-4 py-2.5">Amount</th>
                            <th class="px-4 py-2.5">Method</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($seat->settlementRequests->sortByDesc('id') as $req)
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    Round #{{ $req->period?->period_number ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                                    <div>{{ $req->formatted_payment_date_bs }}</div>
                                    <div class="text-xs text-gray-400">{{ $req->payment_date?->format('M d, Y') }} AD</div>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    Rs. {{ number_format((float) $req->amount, 2) }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                                    <div>{{ $req->payment_method }}</div>
                                    @if ($req->reference_id)
                                        <div class="text-xs text-gray-400">Ref: {{ $req->reference_id }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-amber-500/15 text-amber-600 dark:text-amber-400' => $req->status === 'pending',
                                        'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' => $req->status === 'approved',
                                        'bg-rose-500/15 text-rose-600 dark:text-rose-400' => $req->status === 'rejected',
                                    ])>
                                        {{ $req->status === 'pending' ? 'Submitted for Review' : ucfirst($req->status) }}
                                    </span>
                                    @if ($req->reviewer_notes)
                                        <div class="mt-1 text-xs text-gray-400">{{ $req->reviewer_notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
