<?php

use App\Http\Controllers\API\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth.api'])->group(function () {

    Route::post('/users/{id}/update-balance', function ($id, Request $request) {

        $user = User::findOrFail($id);

        $user->balance = $request->input('balance');
        $user->save();

        return response()->json([
            'message' => 'Balance updated',
            'balance' => $user->balance
        ]);
    });
    Route::get('/users/{id}', [AuthController::class, 'me']);
});
