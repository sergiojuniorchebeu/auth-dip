<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VerificationController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/diplomas/qr/{token}', [VerificationController::class, 'byQr']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/requests', [VerificationController::class, 'index']);
    Route::post('/requests', [VerificationController::class, 'store']);
    Route::get('/diplomas/search', [VerificationController::class, 'search']);
    Route::patch('/requests/{verificationRequest}/decision', [VerificationController::class, 'decide']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
