<?php

namespace Tests\Feature\Guru\Soal;

use App\Models\Guru;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Soal;
use App\Models\Kelas;

beforeEach(function(){
    
    $this->guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory())
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->ujian = $this->guru->ujians()->first();
    $this->soal = $this->ujian->soals()->first();
    $this->endpointUrl = '/api/soal/delete/' . $this->soal->id;
});

afterEach(function(){
    User::where('email', 'LIKE', '%@example%')->forceDelete();
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

it('Should delete soal', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->post($this->endpointUrl);
    
    $response->assertSuccessful();
    $this->assertSoftDeleted($this->soal);
});

it('Should return 404 if soal not found', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl . '34573485');

    // dd($response);
    $response->assertNotFound();
    $response->assertJsonPath("message", "Soal tidak ditemukan");
});

it('Should return 403 if soal is not owned', function(){

    $hacker = Guru::factory()->create();
    $hacker->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $hacker->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl);

    $response->assertForbidden();
    $response->assertJsonPath("message", "Anda tidak dapat menghapus soal orang lain!");
});