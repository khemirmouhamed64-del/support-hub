<?php

use App\Http\Controllers\Api\ApiTicketController;
use Illuminate\Support\Facades\Route;

// ERP -> Hub API (authenticated by client api_key)
Route::middleware('auth.api_client')->group(function () {
    Route::get('/ping', [ApiTicketController::class, 'ping']);
    Route::post('/tickets', [ApiTicketController::class, 'store']);
    Route::post('/tickets/{id}/status', [ApiTicketController::class, 'updateStatus']);
    Route::post('/tickets/{id}/client-response', [ApiTicketController::class, 'clientResponse']);
    Route::post('/tickets/{id}/delete', [ApiTicketController::class, 'deleteTicket']);
});
