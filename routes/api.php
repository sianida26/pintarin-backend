<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\SoalController;
use App\Http\Controllers\MatpelController;

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
    Route::get('/whoAmI', [ AuthController::class, 'whoAmI' ])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::post('/soal', [ SoalController::class, 'createSoal' ]);
    
    Route::apiResource('kelas', KelasController::class);
    Route::post('/kelas/addSiswa', [KelasController::class, 'addSiswa']);
    Route::post('/kelas/acceptSiswa', [KelasController::class, 'acceptSiswa']);
    Route::get('/kelas/{id}/getUjians', [KelasController::class, 'getUjians']);
    Route::get('/kelas/{id}/getSiswa', [KelasController::class, 'getSiswa']);
    Route::get('/kelas/{id}/getWaitingSiswa', [KelasController::class, 'getWaitingSiswa']);
    
    Route::apiResource('matpel', MatpelController::class);
});

Route::group(['prefix' => 'siswa', 'middleware' => ['auth:sanctum','role:siswa']], function(){

    Route::group(['prefix' => 'kelas'], function(){
        Route::get('/', [KelasController::class, 'listKelasForSiswa']);
        Route::post('/enroll', [KelasController::class, 'enroll']);
    });
    
});

Route::apiResource('ujian', UjianController::class);
