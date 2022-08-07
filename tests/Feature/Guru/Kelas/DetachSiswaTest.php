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
    $this->endpointUrl = '/api/kelas/removeSiswa';
    $this->guru = Guru::factory()
         ->has(Kelas::factory())
         ->create();
    $this->guru->user->assignRole('guru');
    
    $this->kelas = $this->guru->kelas()->first();

    $this->siswa = Siswa::factory()
        ->create();
    
    $this->siswa->user->assignRole('siswa');

    $this->kelas->siswas()->attach($this->siswa->id, ['is_waiting' => false]);
});

afterEach(function(){
    User::where('email', 'LIKE', '%@example%')->forceDelete();
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

it('Should detach siswa from kelas', function(){
    
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
    $this->assertDatabaseMissing('kelas_siswa', [
        'siswa_id' => $this->siswa->id,
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
            'siswa_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
        ]);
    
    $response->assertForbidden();
    $response->assertJsonPath('message','Anda tidak dapat mengubah kelas guru lain');

    $this->assertDatabaseHas('kelas_siswa', [
        'siswa_id' => $this->siswa->id,
        'kelas_id' => $this->kelas->id,
        'is_waiting' => false,
    ]);
});

// it('Should return 403 if siswa already removed', function(){

//     $this->siswa->kelas()->detach($this->kelas->id);
    
//     $response = $this
//         ->withHeaders([
//             'Accept' => 'application/json',
//             'Authorization' => 'Bearer ' . $this->guru->user->getAccessToken(),
//         ])
//         ->postJson($this->endpointUrl,[
//             'siswa_id' => $this->siswa->id,
//             'kelas_id' => $this->kelas->id,
//         ]);
    
//     $response->assertForbidden();
//     $response->assertJsonPath('message', 'Siswa tidak masuk ke dalam kelas');
// });


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
