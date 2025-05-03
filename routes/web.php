<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController; // <-- Импорт
use App\Http\Controllers\UserDashboardController;  // <-- Импорт
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request; // <-- Импорт Request

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function (Request $request) {
    if ($request->user()?->is_admin) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('user.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin-dashboard', AdminDashboardController::class)
    ->middleware(['auth', 'verified', 'can:viewAdminDashboard'])
    ->name('admin.dashboard');

Route::get('/user-dashboard', UserDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('user.dashboard');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
