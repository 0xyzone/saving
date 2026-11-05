<div class="text-center py-2 text-xs text-gray-500 dark:text-gray-400">
    <a href="{{ url('/changelog') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span>v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </a>
</div>
