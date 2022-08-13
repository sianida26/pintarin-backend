<?php

namespace Tests\Feature\Siswa\Kelas;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Ujian;
use App\Models\UjianResult;
use App\Models\Siswa;
use App\Models\User;

use Faker\Factory as Faker;

use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        ->has(
            Kelas::factory()
                ->has(
                    Ujian::factory()
                        ->count(10)
                        ->latihan()
                )
                ->has(
                    Ujian::factory()
                        ->count(10)
                        ->ujian()
                )
        )
        ->create();
    
    $this->siswa = Siswa::factory()
        ->create();
    
    $this->user = $this->siswa->user;
    $this->user->assignRole('siswa');

    $this->kelas = $this->guru->kelas()->first();
    $this->kelas->siswas()->attach($this->siswa->id);

    $this->endpointUrl = '/api/siswa/kelas/' . $this->kelas->id;
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

it('Should return 403 if not siswa', function(){
    $this->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertForbidden();
});

it('Should contains kelas name', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('name')
            ->etc()
    );
});

it('Should contains kelas mapel', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('mapel')
            ->etc()
    );
});

it('Should contains nama guru', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('guru')
            ->etc()
    );
});

it('Should contains latihan anbk', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('latihans')
            ->etc()
    );
});

it('Should contains id on latihan anbk', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('latihans.0', fn ($json) => 
                $json->has('id')
                    ->etc()
            )
            ->etc()
    );
});

it('Should contains name on latihan anbk', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('latihans.0', fn ($json) => 
                $json->has('name')
                    ->etc()
            )
            ->etc()
    );
});

it('Should contains ujians', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('ujians')
            ->etc()
    );
});

it('Should contains id on ujian anbk', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('ujians.0', fn ($json) => 
                $json->has('id')
                    ->etc()
            )
            ->etc()
    );
});

it('Should contains name on ujian anbk', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('ujians.0', fn ($json) => 
                $json->has('name')
                    ->etc()
            )
            ->etc()
    );
});

it('Should contains already taken flag on ujian anbk', function(){

    $ujianId = $this->kelas->ujians()->where('isUjian',true)->first()->id;
    UjianResult::create([
        'siswa_id' => $this->siswa->id,
        'ujian_id' => $ujianId,
        'answers' => [['soalId' => 0, 'answer' => 'djkasljdlas']],
        'nilai' => 0,
    ]);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl);
    
    $response->assertSuccessful();
    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('ujians.0', fn ($json) => 
                $json->where('isAlreadyTaken', true)
                    ->etc()
            )
            ->has('ujians.1', fn($json) =>
                $json->where('isAlreadyTaken', false)
                    ->etc()
            )
            ->etc()
    );
});

it('Should return 404 if kelas not found', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->get($this->endpointUrl . '99999');
    
    $response->assertNotFound();
    $response->assertJsonPath('message', 'Kelas tidak ditemukan');
});