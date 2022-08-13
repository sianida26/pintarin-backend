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
                ->has(
                    Soal::factory()
                        ->isian()
                        ->count(10)
                )
                ->count(2)
                ->ujian()
        )
        ->has(Ujian::factory()->count(10)->ujian())
        ->has(Ujian::factory()->count(10)->latihan())
        ->has(Kelas::factory()->count(2))
        ->create();

    $this->kelas = $this->guru->kelas()->first();
    $this->ujians = $this->guru->ujians()->where('isUjian',true)->get();

    $this->siswas = Siswa::factory()->count(2)->create();
    
    //append attach to kelas
    // $this->kelas->ujians()->attach($this->ujians->modelKeys());
    $this->guru->kelas->each(fn($kelas) => $kelas->ujians()->attach($this->ujians->modelKeys()));

    //append siswa to kelas
    // $this->siswas->each(fn($siswa) => $siswa->kelas()->attach($this->kelas->id, ['is_waiting' => false]));
    $this->siswas->first()->kelas()->attach($this->kelas->id, ['is_waiting' => false]);
    $this->siswas->skip(1)->first()->kelas()->attach($this->guru->kelas->skip(1)->first()->id, ['is_waiting' => false]);

    //siswa ambil ujian dan dinilai
    $this->ujians->first()->ujianResults()->create([
        'siswa_id' => $this->siswas->first()->id,
        'answers' => [['soalId' => 1, 'answer' => 'sajddlkfjlsdf']],
        'nilai' => 80,
    ]);

    //siswa ambil ujian namun belum dinilai
    $this->ujians->first()->ujianResults()->create([
        'siswa_id' => $this->siswas->skip(1)->first()->id,
        'answers' => [['soalId' => 1, 'answer' => 'sajddlkfjlsdf']],
        'nilai' => null,
    ]);

    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->endpointUrl = '/api/hasil/';
});

afterEach(function(){
    $user = User::where('email', 'LIKE', '%example%')->forceDelete();
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
    $response->assertJsonCount($this->ujians->count());
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

it('Should not contains latihan', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->get($this->endpointUrl);
    $response->assertSuccessful();
    $response->assertJsonCount(12);
});

it('Should contains id asesmen', function(){
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

it('Should contains nama asesmen', function(){
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
                        ->has('message')
                )
                ->etc()
        )
    );
});

it('status should be 1/2 kelas sudah dinilai if so', function(){

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
                    $json->where('status','PROGRESS')
                        ->where('message','1/2 kelas sudah dinilai')
                )
                ->etc()
        )
    );
});

it('status should be "Belum ada kelas yang dinilai" if so', function(){

    $this->ujians->first()->ujianResults()->update(['nilai' => null]);

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
                    $json->where('status','INCOMPLETE')
                        ->where('message','Belum ada kelas yang dinilai')
                )
                ->etc()
        )
    );
});

it('status should be "Sudah dinilai semua" if so', function(){

    $this->ujians->first()->ujianResults()->update(['nilai' => 80]);

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
                    $json->where('status','COMPLETED')
                        ->where('message','Sudah dinilai semua')
                )
                ->etc()
        )
    );
});

it('status should be "Belum ada hasil asesmen yang masuk" if so', function(){

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
                    $json->where('status','EMPTY')
                        ->where('message','Belum ada hasil asesmen yang masuk')
                )
                ->etc()
        )
    );
});