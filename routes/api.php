<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\SoalController;
use App\Http\Controllers\LatihanController;
use App\Http\Controllers\MatpelController;
use App\Http\Controllers\SiswaController;

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

Route::group(['middleware' => ['auth:sanctum', 'role:guru']], function(){
    
    Route::apiResource('kelas', KelasController::class);
    Route::post('/kelas/addSiswa', [KelasController::class, 'addSiswa']);
    Route::post('/kelas/removeSiswa', [KelasController::class, 'removeSiswa']);
    Route::post('/kelas/acceptSiswa', [KelasController::class, 'acceptSiswa']);
    Route::post('/kelas/declineSiswa', [KelasController::class, 'declineSiswa']);
    Route::get('/kelas/{id}/getUjians', [KelasController::class, 'getUjians']);
    Route::get('/kelas/{id}/getSiswa', [KelasController::class, 'getSiswa']);
    Route::get('/kelas/{id}/getWaitingSiswa', [KelasController::class, 'getWaitingSiswa']);
    Route::post('/kelas/addUjian', [KelasController::class, 'addUjian']);
    Route::post('/kelas/removeUjian', [KelasController::class, 'removeUjian']);
    Route::post('/kelas/edit/{id}', [KelasController::class, 'update']);
    Route::post('/kelas/delete/{id}', [KelasController::class, 'destroy']);

    Route::group(['prefix' => 'ujian'], function(){
        Route::get('/', [UjianController::class, 'index']);
        Route::get('/{id}', [UjianController::class, 'show']);
        Route::post('/edit/{id}', [UjianController::class, 'edit']);
        Route::post('/delete/{id}', [UjianController::class, 'delete']);
    });

    Route::group(['prefix' => 'soal'], function(){
        Route::post('/', [ SoalController::class, 'createSoal' ]);
        Route::get('/{id}', [ SoalController::class, 'detailSoal' ]);
        Route::post('/edit/{id}', [ SoalController::class, 'editSoal' ]);
        Route::post('/delete/{id}', [ SoalController::class, 'deleteSoal' ]);
        Route::post('/uploadImage', [ SoalController::class, 'uploadImage' ]);
    });
    
    Route::group(['prefix' => 'siswa'], function(){
        Route::get('/', [ SiswaController::class, 'all' ]);
    });
    
    Route::apiResource('matpel', MatpelController::class);
});

Route::group(['prefix' => 'siswa', 'middleware' => ['auth:sanctum','role:siswa']], function(){

    Route::group(['prefix' => 'kelas'], function(){
        Route::get('/', [KelasController::class, 'listKelasForSiswa']);
        Route::get('/{id}/getLatihan', [LatihanController::class, 'listLatihanByKelasForSiswa']);
        Route::get('/{id}', [KelasController::class, 'getDetailKelasForSiswa']);
        Route::post('/enroll', [KelasController::class, 'enroll']);
    });

    Route::group(['prefix' => 'latihan'], function(){
        Route::get('/{id}', [LatihanController::class, 'getLatihanById']);
        Route::post('/submit', [LatihanController::class, 'submit']);
    });

    Route::group(['prefix' => 'ujian'], function(){
        Route::get('/{id}', [UjianController::class, 'getUjianById']);
        Route::post('/submit', [UjianController::class, 'submit']);
    });
    
});

Route::apiResource('ujian', UjianController::class);
