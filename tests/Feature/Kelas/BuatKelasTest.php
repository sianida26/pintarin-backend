<?php

namespace Tests\Feature\Soal;

use App\Models\Guru;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\User;

use Faker\Factory as Faker;

beforeEach(function(){
    $this->endpointUrl = '/api/kelas';
    $this->guru = Guru::factory()
         ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
    $this->matpelId = 1;
});

afterEach(function(){ 
    $user = User::where('email', 'LIKE', '%testing%')->forceDelete();
    // User::where('email', 'testing1@test.com')->forceDelete();
    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl);
    $response->assertUnauthorized();
});

it('Should return 403 if not guru', function(){
    $siswa = User::factory()
        ->create();
    $siswa->assignRole('siswa');

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $siswa->getAccessToken(),
        ])
        ->post($this->endpointUrl);
    
    $response->assertForbidden();
});

it('Should create kelas', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'name' => '___testing___',
            'matpelId' => $this->matpelId,
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('kelas',[
        'name' => '___testing___',
        'guru_id' => $this->guru->id,
        'matpel_id' => $this->matpelId,
    ]);
});

it('Should return 422 if name is empty', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'name' => '',
            'matpelId' => $this->matpelId,
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.name.0', 'Harus diisi');
});

it('Should return 422 if name is more than 255 chars', function(){

    $faker = Faker::create();
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'name' => $faker->regexify('\w{256}'),
            'matpelId' => $this->matpelId,
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.name.0', 'Maksimal 255 karakter');
});

it('Should return 422 if matpel is invalid', function(){

    $faker = Faker::create();
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'name' => '___test___',
            'matpelId' => 0,
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.matpelId.0', 'Mata pelajaran tidak ada atau tidak valid');
});

it('Should return 403 if guru komunitas creates matpel other than "kimia"', function(){

    $this->guru->user->assignRole('guru komunitas');
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'name' => '___testing___',
            'matpelId' => $this->matpelId,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message', 'Anda hanya bisa membuat kelas mata kuliah kimia saja');
});