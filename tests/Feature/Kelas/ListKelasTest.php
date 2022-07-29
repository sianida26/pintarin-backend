<?php

namespace Tests\Feature\Ujian;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->endpointUrl = '/api/kelas';
    $this->guru = Guru::factory()
        ->has(Kelas::factory()->count(20)) 
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
});

afterEach(function(){
    // $user = User::where('email', 'LIKE', '%example%')->forceDelete();
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
    $response->assertJsonCount($this->guru->kelas->count());
});

it('Should return pagination data when pages query exists', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '?page=1&perPage=13');
    
    $response->assertSuccessful();

    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('data',13)
             ->etc()
    );

});

it('Should contains enroll link', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();

    $response->assertJson(fn (AssertableJson $json) => 
        $json->whereAllType([
            '0.enrollLink' => 'string',
        ])
    );

});