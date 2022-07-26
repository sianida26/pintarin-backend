<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\ProfileController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/__test__', [ DevController::class, 'test' ]);

Route::middleware('auth:sanctum')->post('/lengkapi-profil-guru', [ ProfileController::class, 'lengkapiDataGuru' ]);

Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [ AuthController::class, 'register' ]);
    Route::post('/login', [ AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
