<?php

namespace Tests\Feature\Siswa\Kelas;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->siswa = Siswa::factory()
    ->hasAttached(
        Kelas::factory()
            ->has(
                Ujian::factory()
                    ->count(10)
                    ->state(new Sequence(
                        ['isUjian' => true],
                        ['isUjian' => false],
                    ))
            )
        ,['is_waiting' => false]
    )
    ->create();
    $this->user = $this->siswa->user;
    $this->user->assignRole('siswa');

    $this->kelas = $this->siswa->kelas()->first();

    $this->endpointUrl = '/api/siswa/kelas/' . $this->kelas->id . '/getLatihan' ;
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%@example%')->forceDelete();
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

it('Should return 403 if not siswa', function(){

    $this->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
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
    $response->assertJsonCount($this->kelas->ujians()->where('isUjian',false)->count());
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

it('Should contains latihan id', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('id')
                ->etc()
        )
    );
});

it('Should contains latihan name', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('name')
                ->etc()
        )
    );
});

it('Should return 404 if kelas is not siswa\'s kelas', function(){

    $kelas = Kelas::factory()->create();

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get('/api/siswa/kelas/' . $kelas->id . '/getLatihan');
    $response->assertNotFound();
    $response->assertJsonpath('message','Kelas tidak ditemukan');
});
