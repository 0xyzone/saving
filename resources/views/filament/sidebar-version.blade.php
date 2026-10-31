<div class="px-4 py-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
    <span>Version</span>
    <a href="{{ url('/changelog') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">
        {{ \App\Models\Changelog::currentVersion() }}
    </a>
</div>
