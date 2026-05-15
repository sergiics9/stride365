<?php

use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\UpdateUserProfileController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\ClubApplicationController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\ComunicadoController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\FeedRecordingController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\SocioController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Públicas
Route::post('auth/login', LoginController::class)->name('api.auth.login');
Route::post('auth/register', RegisterController::class)->name('api.auth.register');

Route::middleware('throttle:6,1')->group(function () {
    Route::post('auth/forgot-password', ForgotPasswordController::class)->name('api.auth.forgot-password');
    Route::post('auth/reset-password', ResetPasswordController::class)->name('api.auth.reset-password');
});

Route::post('webhook/stripe', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// Autenticadas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', LogoutController::class)->name('api.auth.logout');
    Route::get('auth/me', UserController::class)->name('api.auth.me');
    Route::patch('auth/me', UpdateUserProfileController::class)->name('api.auth.me.update');

    Route::prefix('feed')->name('api.feed.')->group(function () {
        Route::get('/', [FeedController::class, 'index'])->name('index');
        Route::post('recordings/start', [FeedRecordingController::class, 'start'])->name('recordings.start');
        Route::patch('recordings/{recording}', [FeedRecordingController::class, 'updateTrack'])
            ->whereNumber('recording')
            ->name('recordings.update');
        Route::post('recordings/{recording}/finish', [FeedRecordingController::class, 'finish'])
            ->whereNumber('recording')
            ->name('recordings.finish');
        Route::post('recordings/import-gpx', [FeedRecordingController::class, 'importGpx'])
            ->name('recordings.import-gpx');
        Route::get('{publicacion}', [FeedController::class, 'show'])
            ->whereNumber('publicacion')
            ->name('show');
        Route::patch('{publicacion}', [FeedController::class, 'update'])
            ->whereNumber('publicacion')
            ->name('update');
        Route::delete('{publicacion}', [FeedController::class, 'destroy'])
            ->whereNumber('publicacion')
            ->name('destroy');
    });

    Route::prefix('subscription')->name('api.subscription.')->group(function () {
        Route::get('memberships', [SubscriptionController::class, 'memberships'])->name('memberships');
        Route::get('status', [SubscriptionController::class, 'status'])->name('status');
        Route::post('checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::get('invoices', [SubscriptionController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}', [SubscriptionController::class, 'downloadInvoice'])
            ->name('invoices.download');
    });

    // Club applications (cualquier usuario puede solicitar; super_admin revisa)
    Route::prefix('clubs/applications')->name('api.club-applications.')->group(function () {
        Route::get('/', [ClubApplicationController::class, 'index'])->name('index');
        Route::post('/', [ClubApplicationController::class, 'store'])->name('store');
        Route::get('{club}', [ClubApplicationController::class, 'show'])->name('show');
        Route::post('{club}/approve', [ClubApplicationController::class, 'approve'])->name('approve');
        Route::post('{club}/reject', [ClubApplicationController::class, 'reject'])->name('reject');
    });

    // Clubes públicos (solo lectura para usuarios autenticados; manage para super_admin / admin del club)
    Route::apiResource('clubes', ClubController::class)
        ->only(['index', 'show', 'update', 'destroy'])
        ->parameters(['clubes' => 'club']);

    // Socios — solo admin_club del club o super_admin
    Route::middleware('club.member:admin')->group(function () {
        Route::apiResource('clubes.socios', SocioController::class)
            ->parameters(['clubes' => 'club', 'socios' => 'socio']);
    });

    // Actividades — admin_club, guia o socio (lectura) del club
    Route::middleware('club.member:admin,socio,guide')->group(function () {
        Route::apiResource('clubes.actividades', ActividadController::class)
            ->parameters(['clubes' => 'club', 'actividades' => 'actividad']);
        Route::post('clubes/{club}/actividades/{actividad}/finish', [ActividadController::class, 'finish'])
            ->name('api.actividades.finish');

        Route::apiResource('clubes.comunicados', ComunicadoController::class)
            ->parameters(['clubes' => 'club', 'comunicados' => 'comunicado']);
    });

    // Inscripciones (anidadas bajo actividades, sin clubId en el path)
    Route::apiResource('actividades.inscripciones', InscripcionController::class)
        ->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['actividades' => 'actividad', 'inscripciones' => 'inscripcion']);
});
