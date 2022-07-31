<?php

namespace Tests\Feature\Ujian;

use App\Models\User;
use App\Models\Guru;
use App\Models\Kelas;

use Faker\Factory as Faker;
use Tests\TestCase;

// beforeAll(function(){
//     $guru = Guru::factory()->create();
// })

beforeEach(function(){
    $this->endpointUrl = '/api/ujian';
    $this->guru = Guru::factory()
         ->has(Kelas::factory())
         ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
    $this->matpelId = 1;

    $this->kelas = $this->guru->kelas()->first();
});

afterEach(function(){
    // $user = User::where('email', 'LIKE', '%@example%')->forceDelete();

    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this->postJson($this->endpointUrl);
    $response->assertStatus(401);
});

it('Should return 200 if success', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => true,
            'kelasId' => $this->kelas->id,
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('ujians', [
        'name' => 'Coba Ujian',
        'category' => 'numerasi',
        'isUjian' => true,
        'kelas_id' => $this->kelas->id,
    ]);
});

it('Should return 422 if name is empty', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => '',
            'category' => 'numerasi',
            'isUjian' => true,
            'kelasId' => $this->kelas->id,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Harus diisi');
});

it('Should return 422 if name length is more than 255', function(){
    $faker = Faker::create();

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => $faker->regexify('\w{256}'),
            'category' => 'numerasi',
            'isUjian' => true,
            'kelasId' => $this->kelas->id,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Maksimal 255 karakter');
});

it('Should return 422 if category is not exists in enum', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'asu',
            'isUjian' => true,
            'kelasId' => $this->kelas->id,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.category.0','Harus berupa literasi atau numerasi');
});

it('Should return 422 if isUjian is not boolean', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => 'hahahaa',
            'kelasId' => $this->kelas->id,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.isUjian.0','Harus berupa boolean');
});