<?php

use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\ComunicadoController;
use App\Http\Controllers\Api\CuotaController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\SocioController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', LoginController::class)->name('api.auth.login');
Route::post('auth/register', RegisterController::class)->name('api.auth.register');

Route::post('webhook/stripe', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', LogoutController::class)->name('api.auth.logout');
    Route::get('auth/me', UserController::class)->name('api.auth.me');

    Route::prefix('feed')->name('api.feed.')->group(function () {
        Route::get('/', [FeedController::class, 'index'])->name('index');
        Route::get('{publicacion}', [FeedController::class, 'show'])->name('show');
    });

    Route::prefix('subscription')->name('api.subscription.')->group(function () {
        Route::get('status', [SubscriptionController::class, 'status'])->name('status');
        Route::post('checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::get('invoices', [SubscriptionController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}', [SubscriptionController::class, 'downloadInvoice'])
            ->name('invoices.download');
    });

    Route::middleware('subscribed')->group(function () {
        Route::apiResource('clubes', ClubController::class)
            ->parameters(['clubes' => 'club']);

        Route::apiResource('clubes.socios', SocioController::class)
            ->parameters(['clubes' => 'club', 'socios' => 'socio']);

        Route::apiResource('clubes.actividades', ActividadController::class)
            ->parameters(['clubes' => 'club', 'actividades' => 'actividad']);

        Route::apiResource('clubes.grupos', GrupoController::class)
            ->parameters(['clubes' => 'club', 'grupos' => 'grupo']);

        Route::apiResource('clubes.cuotas', CuotaController::class)
            ->parameters(['clubes' => 'club', 'cuotas' => 'cuota']);

        Route::apiResource('actividades.inscripciones', InscripcionController::class)
            ->only(['index', 'store', 'show', 'destroy'])
            ->parameters(['actividades' => 'actividad', 'inscripciones' => 'inscripcion']);

        Route::apiResource('grupos.comunicados', ComunicadoController::class)
            ->parameters(['grupos' => 'grupo', 'comunicados' => 'comunicado']);

        Route::apiResource('cuotas.pagos', PagoController::class)
            ->only(['index', 'store', 'show', 'destroy'])
            ->parameters(['cuotas' => 'cuota', 'pagos' => 'pago']);
    });
});
