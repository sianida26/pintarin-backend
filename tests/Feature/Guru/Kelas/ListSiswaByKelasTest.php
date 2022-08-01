<?php

namespace Tests\Feature\Guru\Kelas;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;
use App\Models\Siswa;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(
            Kelas::factory()
                ->hasAttached(
                    Siswa::factory()->count(10),
                    ['is_waiting' => true]
                )
                ->hasAttached(
                    Siswa::factory()->count(10),
                    ['is_waiting' => false]
                )
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
    
    $this->kelas = $this->guru->kelas()->first();

    $this->endpointUrl = '/api/kelas/' . $this->kelas->id . '/getSiswa' ;
});

afterEach(function(){
    // $user = User::where('email', 'LIKE', '%example%')->forceDelete();
    $this->kelas->siswas->each(fn($siswa) => $siswa->user->forceDelete());
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

it('Should return 404 if kelas not found', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get('/api/kelas/' . 'someInvalidId' . '/getWaitingSiswa');
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Kelas tidak ditemukan');
});

it('Should return all data when no pages query sent', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonCount($this->kelas->siswas()->wherePivot('is_waiting', false)->count());
});

it('Should return pagination data when pages query exists', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '?page=1&perPage=5');
    
    $response->assertSuccessful();

    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('data',5)
             ->etc()
    );
});