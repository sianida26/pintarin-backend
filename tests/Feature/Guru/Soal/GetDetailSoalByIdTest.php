<?php

namespace Tests\Feature\Guru\Soal;

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
                ->has(Soal::factory())
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->ujian = $this->guru->ujians()->first();
    $this->soal = $this->ujian->soals->first();

    $this->endpointUrl = '/api/soal/' . $this->soal->id;
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

it('Should contains id soal', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    
    // dd($response);
    $response->assertSuccessful();
    $response->assertJsonPath('id', $this->soal->id);
});

it('Should contains id ujian', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('ujianId', $this->ujian->id);
});

it('Should contains soal type', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('type', $this->soal->type);
});

it('Should contains bobot', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('bobot', $this->soal->bobot);
});

it('Should contains soal', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonPath('soal', $this->soal->soal);
});

it('Should contains answers', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('answers')
            ->etc()
    );
});

it('Should contains answers with array type soal type is pg', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('answers','array')
            ->etc()
    );
});

it('Should contains answers with array type soal type is pgk', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->pgk())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('answers','array')
            ->etc()
    );
});

it('Should contains answers with string type soal type is menjodohkan', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->menjodohkan())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('answers','string')
            ->etc()
    );
});

it('Should contains answers with string type soal type is isian', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->isian())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('answers','string')
            ->etc()
    );
});

it('Should contains answers with string type soal type is uraian', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->uraian())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('answers','string')
            ->etc()
    );
});

it('Should contains answers with pembahasan', function(){

    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->uraian())
        )
        ->create();
    $user = $guru->user;
    $user->assignRole('guru');

    $soal = $guru->ujians()->first()->soals->first();

    $endpointUrl = '/api/soal/' . $soal->id;

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($endpointUrl);
        
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->whereType('pembahasan','string')
            ->etc()
    );
});


it('Should return 404 if soal not found', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '34573485');

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
        ->get($this->endpointUrl);

    $response->assertForbidden();
    $response->assertJsonPath("message", "Anda tidak dapat melihat soal orang lain!");
});