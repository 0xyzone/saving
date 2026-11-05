<div class="px-4 py-2.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
    <span class="font-medium text-gray-400">Release</span>
    <a href="{{ url('/changelog') }}" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-mono text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 hover:border-amber-500/40 transition">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
        <span>v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </a>
</div>
