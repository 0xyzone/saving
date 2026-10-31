<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use App\Services\ChangelogService;
use Illuminate\Contracts\View\View;

class ChangelogController extends Controller
{
    /**
     * Display the public changelog page.
     */
    public function __invoke(): View
    {
        $releases = Changelog::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $currentVersion = ChangelogService::getCurrentVersion();

        return view('pages.changelog', [
            'releases' => $releases,
            'currentVersion' => $currentVersion,
        ]);
    }
}
