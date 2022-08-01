<?php

namespace Tests\Feature\Ujian;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\User;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(
            Kelas::factory()
                ->count(4)
        ) 
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->kelas = $this->guru->kelas()->first();

    $this->endpointUrl = '/api/kelas/' . $this->kelas->id;
});

afterEach(function(){
    User::where('email', 'LIKE', '%@example%')->forceDelete();
    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertUnauthorized();
});

it('Should return 403 if not guru', function(){
    $this->user->syncRoles(['siswa']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertForbidden();
});

it('Should contains kelas name', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('name')
            ->etc()
    );
});

it('Should contains kelas mapel', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('mapel')
            ->etc()
    );
});

it('Should return 404 if kelas not found', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl . '99999');
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Kelas tidak ditemukan');
});

it('Should return 403 if not user\'s kelas', function(){

    $hacker = Guru::factory()
         ->create();
    $hacker->user->assignRole('guru');

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $hacker->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertForbidden();
    $response->assertJsonPath('message', 'Anda hanya dapat melihat detail kelas Anda sendiri');
});