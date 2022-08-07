<?php

namespace Tests\Feature\Guru\Kelas;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\User;

use Faker\Factory as Faker;

beforeEach(function(){
    $this->endpointUrl = '/api/kelas/removeUjian';
    $this->guru = Guru::factory()
         ->has(Kelas::factory())
         ->has(Ujian::factory())
         ->create();

    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
    
    $this->kelas = $this->guru->kelas()->first();

    $this->ujian = $this->guru->ujians()->first();

    $this->kelas->ujians()->attach($this->ujian->id);
});

afterEach(function(){ 
    // $user = User::where('email', 'LIKE', '%testing%')->forceDelete();
    // User::where('email', 'testing1@test.com')->forceDelete();
    User::where('email', 'LIKE', '%@example%')->forceDelete();
    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => $this->ujian->id,
            'kelas_id' => $this->kelas->id,
        ]);
    $response->assertUnauthorized();
});

it('Should return 403 if not guru', function(){

    $this->user->syncRoles(['siswa']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => $this->ujian->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
});

it('Should detach ujian to kelas', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => $this->ujian->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseMissing('kelas_ujian', [
        'ujian_id' => $this->ujian->id,
        'kelas_id' => $this->kelas->id,
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
            'ujian_id' => $this->ujian->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message','Anda tidak dapat mengubah kelas guru lain');

    //Not Changed
    $this->assertDatabaseHas('kelas_ujian', [
        'ujian_id' => $this->ujian->id,
        'kelas_id' => $this->kelas->id
    ]);
});

it('Should return 403 if ujian not added', function(){

    $this->kelas->ujians()->detach($this->ujian->id);
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => $this->ujian->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message', 'Ujian ini tidak berada di dalam kelas');
});

it('Should return 404 if kelas is not found', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => $this->ujian->id,
            'kelas_id' => 99999999,
        ]);
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Kelas tidak ditemukan');
});

it('Should return 404 if ujian is not found', function(){
    
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl,[
            'ujian_id' => 999999999,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Ujian tidak ditemukan');
});
