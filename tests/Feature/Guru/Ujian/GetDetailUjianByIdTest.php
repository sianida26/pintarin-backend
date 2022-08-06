<?php

namespace Tests\Feature\Guru\Ujian;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Soal;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->count(10))
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->ujian = $this->guru->ujians()->first();
    $this->soals = $this->ujian->soals;

    $this->endpointUrl = '/api/ujian/' . $this->ujian->id;
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

it('Should contains id ujian', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('id')
            ->etc()
    );
});

it('Should contains nama ujian', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('name')
            ->etc()
    );
});

it('Should contains kategori', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('category')
            ->etc()
    );
});

it('Should contains jenis (ujian/latihan)', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('type')
            ->etc()
    );
});

it('Should contains soals', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('soals', 10)
            ->etc()
    );
});

it('Should return 404 if ujian not found', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '34573485');

    // dd($response);
    $response->assertNotFound();
    $response->assertJsonPath("message", "Ujian tidak ditemukan");
});

it('Should return 403 if ujian is not owned', function(){

    $hacker = Guru::factory()->create();
    $hacker->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $hacker->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);

    $response->assertForbidden();
    $response->assertJsonPath("message", "Anda tidak dapat melihat ujian orang lain!");
});