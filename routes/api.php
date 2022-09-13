<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/auth/send-verification-sms/{phone}', [AuthController::class, 'sendVerificationSms']);
Route::post('/auth/verify-code/{user}', [AuthController::class, 'verifyCode']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/transactions/collect', [TransactionController::class, 'collect']);
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{user}', [TransactionController::class, 'byUser']);
    Route::post("/auth/logout", [AuthController::class, 'logout']);
});
