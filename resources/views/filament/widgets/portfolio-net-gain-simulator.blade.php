<x-filament-widgets::widget class="sp-calc-widget">
    <div class="sp-calc-card">
        {{-- Ambient motion layer --}}
        <div class="sp-calc-orb sp-calc-orb-2" aria-hidden="true"></div>
        <div class="sp-calc-orb sp-calc-orb-1" aria-hidden="true"></div>
        <div class="sp-calc-sheen" aria-hidden="true"></div>

        <div class="sp-calc-inner">
            {{-- Header with 3D emerald prism & bolder title --}}
            <div class="sp-calc-header">
                <div class="sp-calc-title-group">
                    <div class="sp-calc-3d-stage" aria-hidden="true">
                        <div class="sp-calc-3d-badge">
                            <div class="sp-calc-3d-face sp-calc-3d-face-emerald">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="sp-calc-3d-icon">
                                    <path d="M3 3v18h18"/>
                                    <path d="m19 9-5 5-4-4-3 3"/>
                                </svg>
                            </div>
                            <div class="sp-calc-3d-glow sp-calc-3d-glow-emerald"></div>
                        </div>
                    </div>
                    <div>
                        <h2 class="sp-calc-title">Portfolio Net Gain Simulator</h2>
                        <p class="sp-calc-subtitle">Combined full-scheme net position across all your seats</p>
                    </div>
                </div>

                @if ($portfolio['has_data'])
                    <div class="sp-calc-seat-pills">
                        <span class="sp-calc-pill sp-calc-pill-seats">
                            {{ $portfolio['total_user_seats'] }} {{ $portfolio['total_user_seats'] === 1 ? 'Seat' : 'Seats' }}
                        </span>
                        @if ($portfolio['won_seats_count'] > 0)
                            <span class="sp-calc-pill sp-calc-pill-done">
                                {{ $portfolio['won_seats_count'] }} Disbursed
                            </span>
                        @endif
                        @if ($portfolio['eligible_seats_count'] > 0)
                            <span class="sp-calc-pill sp-calc-pill-eligible">
                                {{ $portfolio['eligible_seats_count'] }} Simulated
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Scheme Selector --}}
            <div class="sp-calc-field">
                <label for="sp-port-scheme" class="sp-calc-label">Scheme</label>
                <select id="sp-port-scheme" wire:model.live="selectedSchemeId" class="sp-calc-select">
                    @forelse ($schemes as $scheme)
                        <option value="{{ $scheme->id }}">{{ $scheme->title }}</option>
                    @empty
                        <option value="">No ongoing schemes</option>
                    @endforelse
                </select>
            </div>

            @if ($portfolio['has_data'])
                @php
                    $combinedGain = (float) $portfolio['combined_net_gain'];
                    $gainPositive = $combinedGain >= 0;
                    $animKey = ($selectedSchemeId ?? '0') . '-' . md5(json_encode($seatTargetPeriods));
                @endphp

                <div class="sp-calc-result-shell" wire:key="sp-port-result-{{ $animKey }}">
                    {{-- Hero Combined Net Gain --}}
                    <div class="sp-calc-hero-row">
                        <div class="sp-calc-hero-block">
                            <span class="sp-calc-eyebrow">
                                Combined Full-Scheme Net Gain ({{ $portfolio['total_user_seats'] }} {{ $portfolio['total_user_seats'] === 1 ? 'Seat' : 'Seats' }})
                            </span>

                            <div class="sp-calc-hero-value {{ $gainPositive ? 'sp-calc-positive' : 'sp-calc-negative' }}">
                                <span class="sp-calc-currency">{{ $gainPositive ? '+Rs.' : '−Rs.' }}</span>
                                <span class="sp-calc-hero-number">{{ number_format(abs($combinedGain), 2) }}</span>
                            </div>
                        </div>

                        <div class="sp-calc-gain-block">
                            <span class="sp-calc-eyebrow">Total Payout vs. Total Outlay</span>
                            <div class="sp-calc-gain-value" style="color: inherit;">
                                Rs. {{ number_format($portfolio['total_payout'], 2) }}
                            </div>
                            <span class="sp-calc-gain-note">
                                vs. Rs. {{ number_format($portfolio['total_outlay'], 2) }} lifetime outlay (all rounds &amp; interest)
                            </span>
                        </div>
                    </div>

                    {{-- Per-Seat Controls & Breakdown --}}
                    <div class="sp-port-seat-list">
                        @foreach ($portfolio['seat_rows'] as $row)
                            @php
                                $rowGain = (float) $row['net_gain'];
                                $rowPositive = $rowGain >= 0;
                            @endphp
                            <div class="sp-port-seat-row">
                                <span class="sp-port-seat-tag">Seat #{{ $row['seat_number'] }}</span>

                                @if ($row['is_won'])
                                    <span class="sp-port-seat-status">
                                        Disbursed in Round #{{ $row['round_number'] }}
                                    </span>
                                @elseif ($upcomingPeriods->isNotEmpty())
                                    <select
                                        wire:model.live="seatTargetPeriods.{{ $row['seat_id'] }}"
                                        class="sp-calc-select sp-port-seat-select"
                                        aria-label="Target round for Seat #{{ $row['seat_number'] }}"
                                    >
                                        @foreach ($upcomingPeriods as $period)
                                            <option value="{{ $period->id }}">
                                                Round #{{ $period->period_number }} — {{ $period->formatted_due_date_bs }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="sp-port-seat-status">No upcoming rounds</span>
                                @endif

                                <span class="sp-port-seat-gain {{ $rowPositive ? 'sp-calc-positive' : 'sp-calc-negative' }}">
                                    {{ $rowPositive ? '+' : '−' }}Rs. {{ number_format(abs($rowGain), 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="sp-calc-empty">
                    No seats found in ongoing schemes.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
