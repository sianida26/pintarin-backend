<?php

namespace Tests\Feature\Guru\Ujian;

use App\Models\User;
use App\Models\Guru;
use App\Models\Kelas;

use Faker\Factory as Faker;
use Tests\TestCase;

// beforeAll(function(){
//     $guru = Guru::factory()->create();
// })

beforeEach(function(){
    $this->endpointUrl = '/api/lengkapi-profil-guru';
    $this->guru = Guru::factory()
         ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->formData = [
        'nip' => 7567953976,
        'nuptk' => 5437945,
        'jabatan' => 'guru',
        'isMale' => true,
        'agama' => 'protestan',
        'address' => 'sdlfjsdf',
        'phone' => '+6283463746834',
        'pendidikanTerakhir' => 'S1',
        'statusKepegawaian' => 'tetap',
    ];
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%@example%')->forceDelete();

    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this->postJson($this->endpointUrl, $this->formData);
    $response->assertStatus(401);
});

it('Should return 200 if success', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, $this->formData);

    $response->assertStatus(200);
    $this->assertDatabaseHas('gurus', [
        'user_id' => $this->user->id,
    ]);
});