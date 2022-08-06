<?php

namespace Tests\Feature\Guru\Soal;

use App\Models\Guru;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Kelas;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function(){

    Storage::fake('soal');

    $this->endpointUrl = '/api/soal/uploadImage';
    $this->guru = Guru::factory()->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->ujian = $this->guru->ujians()->first();

    $this->file = UploadedFile::fake()->image('soal.jpg');
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%@example%')->forceDelete();
    // User::where('email', 'testing1@test.com')->forceDelete();
    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'file' => $this->file,
        ]);
    $response->assertUnauthorized();
});

it('Should upload file', function(){
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer " . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'file' => $this->file,
        ]);
    
    $response->assertSuccessful();
});

it('Should return 403 if not guru', function(){
    
    $this->user->syncRoles(['siswa']);
    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer " . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'file' => $this->file,
        ]);
    
    $response->assertForbidden();
    Storage::disk('soal')->assertMissing($this->file->hashName());
});

it('Should max upload 2 MB');