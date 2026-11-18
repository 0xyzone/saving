<x-filament-widgets::widget class="sp-calc-widget">
    <div class="sp-calc-card">
        {{-- Ambient motion layer --}}
        <div class="sp-calc-orb sp-calc-orb-1" aria-hidden="true"></div>
        <div class="sp-calc-orb sp-calc-orb-2" aria-hidden="true"></div>
        <div class="sp-calc-sheen" aria-hidden="true"></div>

        <div class="sp-calc-inner">
            {{-- Header with 3D isometric coin/prism element & bolder title --}}
            <div class="sp-calc-header">
                <div class="sp-calc-title-group">
                    <div class="sp-calc-3d-stage" aria-hidden="true">
                        <div class="sp-calc-3d-badge">
                            <div class="sp-calc-3d-face sp-calc-3d-front">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="sp-calc-3d-icon">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                    <path d="M2 17l10 5 10-5"/>
                                    <path d="M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <div class="sp-calc-3d-glow"></div>
                        </div>
                    </div>
                    <div>
                        <h2 class="sp-calc-title">Potential Return Estimator</h2>
                        <p class="sp-calc-subtitle">Projected upcoming round disbursement &amp; net position</p>
                    </div>
                </div>

                @if ($projection['has_data'])
                    <div class="sp-calc-seat-pills">
                        <span class="sp-calc-pill sp-calc-pill-seats">
                            {{ $projection['total_user_seats'] }} {{ $projection['total_user_seats'] === 1 ? 'Seat' : 'Seats' }}
                        </span>
                        @if ($projection['can_receive'])
                            <span class="sp-calc-pill sp-calc-pill-eligible">
                                {{ $projection['eligible_seats_left'] }} Eligible Left
                            </span>
                        @else
                            <span class="sp-calc-pill sp-calc-pill-done">
                                All Seats Disbursed
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Compact Selectors --}}
            <div class="sp-calc-controls">
                <div class="sp-calc-field">
                    <label for="sp-calc-scheme" class="sp-calc-label">Scheme</label>
                    <select id="sp-calc-scheme" wire:model.live="selectedSchemeId" class="sp-calc-select">
                        @forelse ($schemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->title }}</option>
                        @empty
                            <option value="">No ongoing schemes</option>
                        @endforelse
                    </select>
                </div>

                <div class="sp-calc-field">
                    <label for="sp-calc-period" class="sp-calc-label">Upcoming Round</label>
                    <select id="sp-calc-period" wire:model.live="selectedPeriodId" class="sp-calc-select" @disabled($upcomingPeriods->isEmpty())>
                        @forelse ($upcomingPeriods as $period)
                            <option value="{{ $period->id }}">
                                Round #{{ $period->period_number }} — {{ $period->formatted_due_date_bs }}
                            </option>
                        @empty
                            <option value="">No upcoming rounds available</option>
                        @endforelse
                    </select>
                </div>
            </div>

            {{-- Projection Display --}}
            @if ($projection['has_data'] && $selectedPeriod)
                @php
                    $animKey = ($selectedSchemeId ?? '0') . '-' . ($selectedPeriodId ?? '0');
                    $netGain = (float) $projection['net_gain'];
                    $gainPositive = $netGain >= 0;
                @endphp

                <div class="sp-calc-result-shell" wire:key="sp-calc-result-{{ $animKey }}">
                    <div class="sp-calc-hero-row">
                        <div class="sp-calc-hero-block">
                            <span class="sp-calc-eyebrow">
                                @if ($projection['can_receive'])
                                    Estimated Net Disbursement (Round #{{ $selectedPeriod->period_number }})
                                @else
                                    No Eligible Seats Remaining
                                @endif
                            </span>

                            <div class="sp-calc-hero-value {{ $projection['can_receive'] ? '' : 'sp-calc-hero-muted' }}">
                                <span class="sp-calc-currency">Rs.</span>
                                <span class="sp-calc-hero-number">{{ number_format($projection['net_disbursement'], 2) }}</span>
                            </div>
                        </div>

                        @if ($projection['total_user_seats'] <= 1)
                            <div class="sp-calc-gain-block">
                                <span class="sp-calc-eyebrow">Projected Net Gain</span>
                                <div class="sp-calc-gain-value {{ $gainPositive ? 'sp-calc-positive' : 'sp-calc-negative' }}">
                                    <span class="sp-calc-gain-sign">{{ $gainPositive ? '+' : '−' }}</span>Rs.&nbsp;{{ number_format(abs($netGain), 2) }}
                                </div>
                                <span class="sp-calc-gain-note">
                                    vs. Rs. {{ number_format($projection['seat_lifetime_outlay'], 2) }} full-scheme seat outlay ({{ $projection['total_periods'] }} rounds{{ $projection['seat_future_interest'] > 0 ? ' + Rs. ' . number_format($projection['seat_future_interest'], 0) . ' future interest' : '' }})
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Compact Breakdown Grid --}}
                    <div class="sp-calc-metrics">
                        <div class="sp-calc-metric">
                            <span class="sp-calc-metric-label">Projected Pool</span>
                            <span class="sp-calc-metric-value">Rs. {{ number_format($projection['projected_gross_pool'], 2) }}</span>
                        </div>
                        <div class="sp-calc-metric">
                            <span class="sp-calc-metric-label">Your Round Dues ({{ $projection['total_user_seats'] }} {{ $projection['total_user_seats'] === 1 ? 'seat' : 'seats' }})</span>
                            <span class="sp-calc-metric-value sp-calc-deduct">−Rs. {{ number_format($projection['user_own_dues'], 2) }}</span>
                        </div>
                        <div class="sp-calc-metric">
                            <span class="sp-calc-metric-label">Maslanda Deposit</span>
                            <span class="sp-calc-metric-value sp-calc-deduct">−Rs. {{ number_format($projection['maslanda_deposit'], 2) }}</span>
                        </div>
                        <div class="sp-calc-metric">
                            <span class="sp-calc-metric-label">Paid So Far</span>
                            <span class="sp-calc-metric-value">Rs. {{ number_format($projection['paid_so_far'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="sp-calc-empty">
                    No upcoming unvalidated rounds are available for this scheme.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
