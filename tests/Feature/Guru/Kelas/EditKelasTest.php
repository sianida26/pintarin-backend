<?php

namespace Tests\Feature\Guru\Kelas;

use App\Models\Guru;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\Kelas;
use App\Models\User;

use Faker\Factory as Faker;

beforeEach(function(){
    
    $this->guru = Guru::factory()
         ->has(Kelas::factory())
         ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->kelas = $this->guru->kelas()->first();

    $this->matpelId = 1;
    $this->endpointUrl = '/api/kelas/edit/' . $this->kelas->id;
});

afterEach(function(){ 
    User::where('email', 'LIKE', '%testing%')->forceDelete();
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
    $this->user->syncRoles(['siswa']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->post($this->endpointUrl);
    
    $response->assertForbidden();
});

it('Should edit kelas', function(){
    
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
    $this->assertDatabaseMissing('kelas',[
        'name' => $this->kelas->name,
        'guru_id' => $this->guru->id,
        'matpel_id' => $this->kelas->matpel_id,
    ]);
    $this->assertDatabaseHas('kelas',[
        'name' => '___testing___',
        'guru_id' => $this->guru->id,
        'matpel_id' => $this->matpelId,
    ]);
});

it('Should return response kelas id', function(){
    
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
    $response->assertJsonPath('id', $this->guru->kelas()->first()->id);
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