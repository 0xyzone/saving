<?php

namespace App\Http\Controllers;

use App\Services\ChangelogService;
use Illuminate\Contracts\View\View;

class ChangelogController extends Controller
{
    /**
     * Display the public changelog page parsed directly from CHANGELOG.md.
     */
    public function __invoke(): View
    {
        $releases = ChangelogService::getReleases();
        $currentVersion = ChangelogService::getCurrentVersion();

        return view('pages.changelog', [
            'releases' => $releases,
            'currentVersion' => $currentVersion,
        ]);
    }
}
