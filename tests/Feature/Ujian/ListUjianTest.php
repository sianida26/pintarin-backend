<?php

namespace Tests\Feature\Ujian;

use App\Models\Guru;
use App\Models\User;
use App\Models\Ujian;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->endpointUrl = '/api/ujian';
    $this->guru = Guru::factory()
        ->has(Ujian::factory()->count(20))
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

it('Should return all data when no pages query sent', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonCount(20);
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
