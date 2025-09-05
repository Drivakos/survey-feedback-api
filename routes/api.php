<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public authentication routes
Route::middleware('rate.limit.ip')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public survey routes
    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::get('/surveys/{id}', [SurveyController::class, 'show']);
});

// Protected routes (require JWT authentication)
Route::middleware(['rate.limit.ip', 'auth:api'])->group(function () {
    Route::post('/surveys/{id}/submit', [SurveyController::class, 'submit']);
    Route::get('/me', [SurveyController::class, 'me']);
});
