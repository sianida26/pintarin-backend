<?php

namespace Tests\Feature\Soal;

use App\Models\Guru;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Kelas;

use Faker\Factory as Faker;

beforeEach(function(){
    $this->endpointUrl = '/api/kelas/enroll';
    $this->siswa = Siswa::factory()
         ->create();
    $this->user = $this->siswa->user;
    $this->user->assignRole('siswa');

    $this->guru = Guru::factory()
        ->has(Kelas::factory()->count(1))
        ->create();
    
    $this->kelas = $this->guru->kelas->first();
});

afterEach(function(){ 
    // $user = User::where('email', 'LIKE', '%testing%')->forceDelete();
    // User::where('email', 'testing1@test.com')->forceDelete();
    $this->user->forceDelete();
    $this->guru->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->post($this->endpointUrl);
    $response->assertUnauthorized();
});

it('Should return 403 if not siswa', function(){
    $this->siswa->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->post($this->endpointUrl);
    
    $response->assertForbidden();
    $this->assertDatabaseMissing('kelas_siswa',[
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
    ]);
});

it('Should enroll kelas', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'token' => $this->kelas->getEnrollToken(),
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('kelas_siswa',[
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
    ]);
});

it('Should set status as waiting', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'token' => $this->kelas->getEnrollToken(),
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('kelas_siswa',[
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
        'is_waiting' => true,
    ]);
});

it('Should return 403 if already enrolled', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'token' => $this->kelas->getEnrollToken(),
        ]);

    $response->assertSuccessful();

    $response2 = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'token' => $this->kelas->getEnrollToken(),
        ]);

    $response2->assertForbidden();
    $response2->assertJsonPath('message', 'Kelas sudah ter-enrol');
});

it('Should return 404 if token is invalid', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'token' => $this->kelas->getEnrollToken() . 'wkwk',
        ]);
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Token tidak valid');
    $this->assertDatabaseMissing('kelas_siswa',[
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
    ]);
});