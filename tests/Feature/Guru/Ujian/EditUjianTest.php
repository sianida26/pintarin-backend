<?php

namespace Tests\Feature\Guru\Ujian;

use App\Models\User;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Ujian;

use Faker\Factory as Faker;
use Tests\TestCase;

// beforeAll(function(){
//     $guru = Guru::factory()->create();
// })

beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(Ujian::factory())
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');
    
    $this->ujian = $this->guru->ujians()->first();
    
    $this->matpelId = 1;

    $this->endpointUrl = '/api/ujian/edit/' . $this->ujian->id;
});

afterEach(function(){
    User::where('email', 'LIKE', '%@example%')->forceDelete();

    $this->user->forceDelete();
});

it('Should return 401 if unauthenticated', function(){
    $response = $this->postJson($this->endpointUrl);
    $response->assertStatus(401);
});

it('Should return 200 if success', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Edit Ujian',
            'category' => 'numerasi',
            'isUjian' => false,
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('ujians', [
        'name' => 'Coba Edit Ujian',
        'category' => 'numerasi',
        'isUjian' => false,
        'guru_id' => $this->guru->id,
    ]);
});

it('Should return 422 if name is empty', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => '',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Harus diisi');
});

it('Should return 422 if name length is more than 255', function(){
    $faker = Faker::create();

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => $faker->regexify('\w{256}'),
            'category' => 'numerasi',
            'isUjian' => true,
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0','Maksimal 255 karakter');
});

it('Should return 422 if category is not exists in enum', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'asu',
            'isUjian' => true,
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.category.0','Harus berupa literasi atau numerasi');
});

it('Should return 422 if isUjian is not boolean', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => 'hahahaa',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.isUjian.0','Harus berupa boolean');
});

it('Should return ujian id if success', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath("id", $this->guru->ujians()->first()->id);
});

it('Should return 404 if ujian not found', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl . '346847', [
            'name' => 'Coba Ujian',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);

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
        ->postJson($this->endpointUrl, [
            'name' => 'Coba Edit Ujian',
            'category' => 'numerasi',
            'isUjian' => true,
        ]);

    $response->assertForbidden();
    $response->assertJsonPath("message", "Anda tidak dapat mengedit ujian orang lain!");
});