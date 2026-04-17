<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CountryController;
use App\Http\Controllers\API\DepositController;
use App\Http\Controllers\API\DepositUssdController;
use App\Http\Controllers\API\OperatorController;
use App\Http\Controllers\API\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');
//Route::get('countries', [DepositController::class, 'getCountries']);
Route::resource('countries', CountryController::class);
Route::resource('operators', OperatorController::class);
Route::get('operators/countries/{country_id}', [OperatorController::class, 'operatorbyCountry']);
Route::get('operators-country-code', [OperatorController::class, 'getOperatorsList']);
Route::get('/users', [AuthController::class, 'getUsers']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('change_password', [AuthController::class, 'changePassword']);
    Route::post('profile/update', [AuthController::class, 'updateProfile']);
    Route::prefix('services')->group(function () {

        // 🔥 Mobile
        Route::get('/', [ServiceController::class, 'index']);

        // 🔧 Admin
        Route::get('/all', [ServiceController::class, 'all']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{id}', [ServiceController::class, 'update']);
        Route::patch('/{id}/toggle', [ServiceController::class, 'toggle']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
    });
    Route::prefix('deposits')->group(function () {

        // 🔥 Mobile
        Route::get('/', [DepositController::class, 'myDeposits']);

        // 🔧 Admin
        Route::get('/all', [DepositController::class, 'all']);
        Route::post('/', [DepositController::class, 'createDeposit']);

    });
});
Route::post('webhook', [DepositController::class, 'webhook'])->name('deposit.webhook');
Route::apiResource('deposit_ussds', DepositUssdController::class);

Route::post(
    'deposit_ussds/{deposit_ussd}/upload-proof',
    [DepositUssdController::class, 'uploadProof']
);
