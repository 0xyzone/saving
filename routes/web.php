<?php

use App\Http\Controllers\ChangelogController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/changelog', ChangelogController::class)->name('changelog');

Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim(str_replace(['../', '..\\'], '', $path), '/');

    abort_unless(Storage::disk('public')->exists($cleanPath), 404);

    return response()->file(Storage::disk('public')->path($cleanPath));
})->where('path', '.*')->name('storage.public');
