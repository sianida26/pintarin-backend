<?php

namespace Tests\Feature\Siswa\Kelas;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;
use App\Models\Soal;

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
                        ->has(
                            Soal::factory()
                                ->count(20)
                        )
                        ->state(['isUjian' => false])
                )
            ,['is_waiting' => false]
        )
        ->create();
    $this->user = $this->siswa->user;
    $this->user->assignRole('siswa');

    $this->ujian = $this->siswa->kelas()->first()->ujians()->first();

    $this->endpointUrl = '/api/siswa/latihan/' . $this->ujian->id;
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

it('Should return latihan name', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('name', $this->ujian->name);
});

it('Should return guru name', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('guru', $this->ujian->guru->user->name);
});

it('Should return latihan category', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('category', $this->ujian->category);
});

it('Should contains same amounts of soal', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response
        ->assertJson(fn(AssertableJson $json) =>
            $json->has('data', $this->ujian->soals()->count())
                ->etc()
    );
});

it('Should contains soal', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response
        ->assertJson(fn(AssertableJson $json) =>
            $json->has('data', $this->ujian->soals()->count(), fn($json)=>
                $json->has('soal')
                    ->etc()
                )
                ->etc()
    );
});

it('Should contains soal and answers for pg type', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response
        ->assertJson(fn(AssertableJson $json) =>
            $json->has('data', $this->ujian->soals()->count(), fn($json)=>
                $json->has('soal')
                    ->has('jawabans')
                    ->etc()
                )
                ->etc()
    );
});

it('Should contains soal id', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response
        ->assertJson(fn(AssertableJson $json) =>
            $json->has('data', $this->ujian->soals()->count(), fn($json)=>
                $json->has('soal_id')
                    ->etc()
                )
                ->etc()
    );
});

it('Should contains soal pembahasan', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response
        ->assertJson(fn(AssertableJson $json) =>
            $json->has('data', $this->ujian->soals()->count(), fn($json)=>
                $json->has('pembahasan')
                    ->etc()
                )
                ->etc()
    );
});