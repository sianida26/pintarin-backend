<?php

namespace Tests\Feature\Guru\Ujian;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;
use App\Models\Soal;
use App\Models\Siswa;
use App\Models\UjianResult;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){
    $this->guru = Guru::factory()
        //Ujian.0 : Ambil ujian dan sudah dikoreksi
        //Ujian.1 : Ambil ujian belum dikoreksi
        ->has(
            Ujian::factory()
                ->ujian()
        )
        ->has(Kelas::factory())
        ->create();

    $this->kelas = $this->guru->kelas->first();
    $this->ujian = $this->guru->ujians()->first();

    $this->siswas = Siswa::factory()->count(10)->create();
    
    //attach ujian to kelas
    $this->kelas->ujians()->attach($this->ujian->id);

    //append siswa to kelas
    $this->siswas->each(fn($siswa) => $siswa->kelas()->attach($this->kelas->id, ['is_waiting' => false]));

    //siswa ambil ujian dan dinilai
    $this->ujian->ujianResults()->create([
        'siswa_id' => $this->siswas->first()->id,
        'answers' => [['soalId' => 1, 'answer' => 'sajddlkfjlsdf']],
        'nilai' => 80,
    ]);

    //siswa ambil ujian namun belum dinilai
    $this->ujian->ujianResults()->create([
        'siswa_id' => $this->siswas->skip(1)->first()->id,
        'answers' => [['soalId' => 1, 'answer' => 'sajddlkfjlsdf']],
        'nilai' => null,
    ]);

    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->endpointUrl = '/api/hasil/' . $this->ujian->id . '/' . $this->kelas->id;
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%@example%')->forceDelete();
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

it('Should return all data when no pages query sent', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonCount($this->siswas->count());
});

it('Should return pagination data when pages query exists', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl . '?page=1&perPage=3');
    
    $response->assertSuccessful();

    $response->assertJson(fn (AssertableJson $json) => 
        $json->has('data',3)
             ->etc()
    );

});

it('Should contains id hasil ujian', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('id')
                ->etc()
        )
    );
});

it('Should contains nama siswa', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('name')
                ->etc()
        )
    );
});

it('Should contains waktu submit', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('submitAt')
                ->etc()
        )
    );
});

it('Should contains status', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('status', fn($json) => 
                    $json->has('status')
                        ->has('score')
                )
                ->etc()
        )
    );
});

it('status should contains score if been reviewed', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('status', fn($json) => 
                    $json->where('status','REVIEWED')
                        ->where('score',80)
                )
                ->etc()
        )
    );
});

it('status should be "Belum dinilai" if so', function(){

    $this->ujian->ujianResults()->update(['nilai' => null]);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->first(fn ($json) =>
            $json->has('status', fn($json) => 
                    $json->where('status','NOT REVIEWED')
                        ->where('score',null)
                )
                ->etc()
        )
    );
});

it('status should be "Belum submit" if so', function(){

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJson(fn(AssertableJson $json) =>
        $json->has('3',fn ($json) =>
            $json->has('status', fn($json) => 
                    $json->where('status','NOT SUBMIT')
                        ->where('score',null)
                )
                ->etc()
        )
    );
});