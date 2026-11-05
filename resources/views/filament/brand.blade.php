<div class="flex items-center gap-2.5">
    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-tr from-amber-500 to-amber-600 text-white font-bold text-sm shadow-md shadow-amber-500/20 font-mono">
        S
    </div>
    <span class="text-base font-bold tracking-tight text-gray-950 dark:text-white">
        Saving Portal
    </span>
    <a href="{{ url('/changelog') }}"
       title="View Changelog"
       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-mono text-[11px] font-semibold tracking-tight bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 hover:bg-amber-500/20 hover:border-amber-500/40 transition">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span>v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </a>
</div>

