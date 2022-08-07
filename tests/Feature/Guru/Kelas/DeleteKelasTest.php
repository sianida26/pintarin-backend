<?php

namespace Tests\Feature\Guru\Kelas;

use App\Models\Guru;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Soal;
use App\Models\Kelas;

beforeEach(function(){
    
    $this->guru = Guru::factory()
        ->has(
            Kelas::factory()
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->kelas = $this->guru->kelas()->first();
    $this->endpointUrl = '/api/kelas/delete/' . $this->kelas->id;
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

it('Should delete kelas', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->post($this->endpointUrl);
    
    $response->assertSuccessful();
    $this->assertModelMissing($this->kelas);
});

it('Should return 404 if kelas not found', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl . '34573485');

    // dd($response);
    $response->assertNotFound();
    $response->assertJsonPath("message", "Kelas tidak ditemukan");
});

it('Should return 403 if kelas is not owned', function(){

    $hacker = Guru::factory()->create();
    $hacker->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $hacker->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl);

    $response->assertForbidden();
    $response->assertJsonPath("message", "Anda tidak dapat menghapus kelas orang lain!");
});