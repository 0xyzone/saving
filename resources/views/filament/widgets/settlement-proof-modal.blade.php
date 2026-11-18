<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-white/10 dark:bg-white/5">
        <div>
            <span class="text-slate-500 dark:text-slate-400">Member:</span>
            <strong class="text-slate-900 dark:text-white">{{ $request->user?->name ?? 'Member' }}</strong>
            <span class="text-slate-400">&middot; Seat #{{ $request->seat?->seat_number ?? '?' }}</span>
        </div>
        <div>
            <span class="text-slate-500 dark:text-slate-400">Amount:</span>
            <strong class="text-emerald-600 dark:text-emerald-400">Rs. {{ number_format((float) $request->amount, 2) }}</strong>
            <span class="text-slate-400">({{ $request->payment_method }})</span>
        </div>
    </div>

    @if (empty($request->proof_image_urls))
        <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400 dark:border-white/10">
            No proof images attached to this request.
        </p>
    @else
        <div
            x-data="{ activeIndex: 0, urls: @js($request->proof_image_urls) }"
            class="space-y-3"
        >
            <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-950/90 p-3 dark:border-white/10">
                <img
                    :src="urls[activeIndex]"
                    alt="Settlement Proof"
                    style="max-height: 62vh; max-width: 100%; width: auto; height: auto; object-fit: contain;"
                    class="rounded-lg"
                />
            </div>

            @if (count($request->proof_image_urls) > 1)
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @foreach ($request->proof_image_urls as $idx => $url)
                        <button
                            type="button"
                            @click="activeIndex = {{ $idx }}"
                            :class="activeIndex === {{ $idx }} ? 'ring-2 ring-amber-500 opacity-100' : 'opacity-60 hover:opacity-100'"
                            class="overflow-hidden rounded-lg border border-slate-200 bg-white p-0.5 transition dark:border-white/10 dark:bg-slate-900"
                        >
                            <img src="{{ $url }}" alt="Proof thumbnail {{ $idx + 1 }}" class="h-14 w-14 rounded object-cover" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
