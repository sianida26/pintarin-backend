<?php

namespace Tests\Feature\Ujian;

use App\Models\User;
use App\Models\Guru;

use Faker\Factory as Faker;
use Tests\TestCase;

const ENDPOINT_URL = '/api/ujian';

// beforeAll(function(){
//     $guru = Guru::factory()->create();
// })

afterEach(function(){
    $user = User::where('email', 'LIKE', '%example%')->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this->postJson(ENDPOINT_URL);
    $response->assertStatus(401);
});

it('Should return 200 if success', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('ujians', [
        'name' => 'Coba Ujian',
        'category' => 'numerasi',
        'isUjian' => true,
        'guru_id' => $guru->id,
    ]);
});

it('Should return 422 if name is empty', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => '',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Harus diisi');
});

it('Should return 422 if name length is more than 255', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;
    $faker = Faker::create();

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => $faker->regexify('\w{256}'),
            'category' => 'numerasi',
            'isUjian' => true,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Maksimal 255 karakter');
});

it('Should return 422 if category is not exists in enum', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => 'Coba Ujian',
            'category' => 'asu',
            'isUjian' => true,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.category.0','Harus berupa literasi atau numerasi');
});

it('Should return 422 if isUjian is not boolean', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => 'hahahaa',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.isUjian.0','Harus berupa boolean');
});

it('Should return 422 if guru id is not exists', function(){

    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson(ENDPOINT_URL, [
            'name' => 'Coba Ujian',
            'category' => 'asu',
            'isUjian' => true,
            'guruId' => 99999999,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.guruId.0','Guru tidak ada');
});