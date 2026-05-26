<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ClubApplicationController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => config('app.name'), 'status' => 'ok']);
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'role:super_admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('clubes', [ClubController::class, 'index'])->name('clubes.index');
        Route::get('clubes/{club}', [ClubController::class, 'show'])->name('clubes.show');
        Route::post('clubes/{club}/toggle-active', [ClubController::class, 'toggleActive'])
            ->name('clubes.toggle-active');

        Route::get('solicitudes', [ClubApplicationController::class, 'index'])->name('solicitudes.index');
        Route::get('solicitudes/{club}', [ClubApplicationController::class, 'show'])->name('solicitudes.show');
        Route::post('solicitudes/{club}/approve', [ClubApplicationController::class, 'approve'])
            ->name('solicitudes.approve');
        Route::post('solicitudes/{club}/reject', [ClubApplicationController::class, 'reject'])
            ->name('solicitudes.reject');

        Route::get('usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('usuarios/{usuario}', [UserController::class, 'show'])->name('usuarios.show');
    });
});
