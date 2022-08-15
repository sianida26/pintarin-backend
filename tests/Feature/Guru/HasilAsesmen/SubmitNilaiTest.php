<?php

namespace Tests\Feature\Siswa\Kelas;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\User;
use App\Models\Ujian;
use App\Models\Soal;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Testing\Fluent\AssertableJson;

use Tests\TestCase;


beforeEach(function(){

    $this->faker = Faker::create();

    //Generate guru, ujian, and kelas
    $guru = Guru::factory()
        ->has(
            Ujian::factory()
                ->has(Soal::factory()->count(10))
                ->has(Soal::factory()->count(10)->pgk())
                ->has(Soal::factory()->count(10)->isian())
                ->has(Soal::factory()->count(10)->uraian())
                ->ujian()
        )
        ->has(Kelas::factory())
        ->create();
    
    $this->ujian = $guru->ujians()->first();
    $kelas = $guru->kelas()->first();

    //Attach kelas to ujian
    $kelas->ujians()->attach([$this->ujian->id]);

    //Attach kelas to siswa
    $this->siswa = Siswa::factory()
        ->hasAttached($kelas, ['is_waiting' => false])
        ->create();

    $this->user = $this->siswa->user;
    $this->user->assignRole('siswa');

    //Generate mock answers
    $this->answers = $this->ujian->soals
        ->shuffle()
        ->take(9)
        ->map(function($soal){

            if ($soal->type === "pg")
                return [
                    'soalId' => $soal->id,
                    'answer' => $this->faker->randomElement([ null,1,2,3,4])
                ];
            
            if ($soal->type === "pgk")
                return [
                    'soalId' => $soal->id,
                    'answer' => $this->faker->randomElement(
                        [
                            null,
                            $this->faker->randomElements([1,2,3,4], $this->faker->numberBetween(0,4))
                        ]
                    )
                ];
            
            if ($soal->type === "menjodohkan")
                return [
                    'soalId' => $soal->id,
                    'answer' => $this->faker->bothify('?-#'),
                ];
            
            if ($soal->type === "isian")
                return [
                    'soalId' => $soal->id,
                    'answer' => $this->faker->sentence(7),
                ];
            
            if ($soal->type === "uraian")
                return [
                    'soalId' => $soal->id,
                    'answer' => $this->faker->sentence(27),
                ];
        });

    $this->endpointUrl = '/api/siswa/ujian/submit';
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
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'answers' => $this->answers,
        ]);
    $response->assertUnauthorized();
});

it('Should return 403 if not siswa', function(){

    $this->user->syncRoles(['guru']);

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'answers' => $this->answers,
        ]);
    
    $response->assertForbidden();
});

it('Should success submit ujian', function(){
    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
            'Accept' => 'application/json',
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'answers' => $this->answers,
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('ujian_results', [
        'siswa_id' => $this->siswa->id,
        'ujian_id' => $this->ujian->id,
    ]);
});
