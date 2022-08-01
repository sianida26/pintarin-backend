<?php

namespace Tests\Feature\Guru;

use App\Models\User;
use App\Models\Guru;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

const ENDPOINT_URL = '/api/lengkapi-profil-guru';

// beforeAll(function(){
//     $guru = Guru::factory()->create();
// });

afterEach(function(){
    $user = User::where('email', 'LIKE', '%example%')->forceDelete();
});

it('Should return 405 other than POST method', function () {
    
    $this->get(ENDPOINT_URL)->assertStatus(405);
    $this->put(ENDPOINT_URL)->assertStatus(405);
    $this->delete(ENDPOINT_URL)->assertStatus(405);
});

it('Should return 401 if unauthenticated', function(){
    $response = $this->postJson(ENDPOINT_URL);
    $response->assertStatus(401);
});

it('Should return 200 if success', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    Storage::fake('avaters');

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
        ])
        ->postJson(ENDPOINT_URL, [
            'foto' => $file,
            'nip' => 32453,
            'nuptk' => 33453434,
            'jabatan' => 'guru',
            'jk' => 'Perempuan',
            'agama' => 'Konghucu',
            'alamat' => 'Jl. in aja dulu',
            'phone' => '077854546456',
            'statusKepegawaian' => 'Guru Tetap',
            'pendidikanTerakhir' => 'S4',
    ]);
    $response->assertStatus(200);
});

it('Should return 422 if data is not valid', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    Storage::fake('avaters');

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
        ])
        ->postJson(ENDPOINT_URL, [
            'foto' => $file,
            'nip' => 32453,
            'nuptk' => 33453434,
            'jabatan' => 'guru',
            'jk' => 'Perempuan',
            'agama' => 'Konghucu',
            'alamat' => 'Jl. in aja dulu',
            'phone' => '', //Should fails
            'statusKepegawaian' => 'Guru Tetap',
            'pendidikanTerakhir' => 'S4',
    ]);
    $response->assertStatus(422);
});