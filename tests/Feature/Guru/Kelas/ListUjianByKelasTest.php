<?php

namespace Tests\Feature\Guru\Kelas;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->hasAttached(Kelas::factory()->count(10))
                ->count(10)
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->kelas = Kelas::factory()
        ->state(['guru_id' => $this->guru->id ])
        ->create();
    
    $this->kelas
        ->ujians()
        ->sync(
            $this->guru
                ->ujians
                ->map(
                    fn($ujian) => $ujian->id
                )
                ->shuffle()
                ->take(6)
        );

    $this->endpointUrl = '/api/kelas/' . $this->kelas->id . '/getUjians' ;
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%example%')->forceDelete();
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
    $siswa = User::factory()
        ->create();
    $siswa->assignRole('siswa');

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $siswa->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertForbidden();
});

it('Should return all data when no pages query sent', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonCount(6);
});

it('Should return pagination data when pages query exists', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '?page=1&perPage=3');
    
    $response->assertSuccessful();

    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('data',3)
             ->etc()
    );

});