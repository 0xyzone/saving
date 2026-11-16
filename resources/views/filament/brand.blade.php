<div class="sp-brand inline-flex items-center gap-2 whitespace-nowrap">
    <img src="{{ asset('images/logo-light.svg') }}" alt="Saving Portal"
        class="sp-logo-light h-8 w-8 shrink-0 dark:hidden" />
    <img src="{{ asset('images/logo-dark.svg') }}" alt="Saving Portal"
        class="sp-logo-dark hidden h-8 w-8 shrink-0 dark:block" />
    <span class="sp-brand-title text-sm font-bold tracking-tight text-amber-950 dark:text-amber-500 whitespace-nowrap">
        Saving Portal
    </span>
    <span
        class="sp-brand-version inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full font-mono text-[10px] font-semibold tracking-tight bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 whitespace-nowrap shrink-0">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span>v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </span>
</div>
