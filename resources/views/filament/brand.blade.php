<div class="flex items-center gap-2.5">
    <img src="{{ asset('images/logo-light.svg') }}" alt="Saving Portal"
        class="sp-logo-light h-8 w-8 shrink-0 dark:hidden" />
    <img src="{{ asset('images/logo-dark.svg') }}" alt="Saving Portal"
        class="sp-logo-dark hidden h-8 w-8 shrink-0 dark:block" />
    <span class="sp-brand-title text-base font-bold tracking-tight text-amber-950 dark:text-amber-500">
        Saving Portal
    </span>
    <a href="{{ url('/changelog') }}" title="View Changelog"
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono text-[11px] font-semibold tracking-tight bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 hover:bg-amber-500/20 hover:border-amber-500/40 transition">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span>v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </a>
</div>
