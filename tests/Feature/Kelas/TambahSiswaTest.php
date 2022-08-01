<?php

namespace Tests\Feature\Soal;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\User;

use Faker\Factory as Faker;

beforeEach(function(){
    $this->endpointUrl = '/api/kelas/addSiswa';
    $this->guru = Guru::factory()
         ->has(Kelas::factory())
         ->create();
    $this->guru->user->assignRole('guru');
    
    $this->kelas = $this->guru->kelas()->first();

    $this->siswa = Siswa::factory()
        ->create();
    
    $this->siswa->user->assignRole('siswa');
});

afterEach(function(){ 
    // $user = User::where('email', 'LIKE', '%testing%')->forceDelete();
    // User::where('email', 'testing1@test.com')->forceDelete();
    $user = User::where('email', 'LIKE', '%example%')->forceDelete();
    $this->guru->user->forceDelete();
    $this->siswa->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    $response->assertUnauthorized();
});

it('Should return 403 if not guru', function(){

    $this->guru->user->syncRoles(['siswa']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
});

it('Should add siswa to kelas', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('kelas_siswa', [
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
    ]);
});

it('is_waiting property of added siswa should false', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('kelas_siswa', [
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
        'is_waiting' => false,
    ]);
});

it('Should return 403 if not user\'s created kelas', function(){

    $hacker = Guru::factory()
         ->create();
    $hacker->user->assignRole('guru');
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $hacker->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message','Anda tidak dapat mengubah kelas guru lain');

    $this->assertDatabaseMissing('kelas_siswa', [
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
        'is_waiting' => false,
    ]);
});

it('Should return 403 if siswa already added', function(){

    $this->siswa->kelas()->attach($this->kelas, ['is_waiting' => false]);
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message', 'Siswa telah ditambahkan sebelumnya');
});

it('Should return 403 if siswa already been in waiting list', function(){

    $this->siswa->kelas()->attach($this->kelas);
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message', 'Siswa telah berada di daftar siswa yang mengajukan kelas');
});

it('Should return 404 if siswa is not found', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => 999999,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Siswa tidak ditemukan');
});

it('Should return 404 if kelas is not found', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'siswa_id' => $this->siswa->id,
            'kelas_id' => 99999999,
        ]);
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Kelas tidak ditemukan');
});
