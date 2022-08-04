<?php

namespace Tests\Feature\Guru\Soal;

use App\Models\Guru;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Kelas;

beforeEach(function(){
    $this->endpointUrl = '/api/soal';
    $this->guru = Guru::factory()
        ->has(
            Ujian::factory()
        )
        ->create();
    $this->user = $this->guru->user;
    $this->user->assignRole('guru');

    $this->ujian = $this->guru->ujians()->first();
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
        ->post($this->endpointUrl);
    $response->assertUnauthorized();
});

it('Should create soal with type pg', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => 2,
            'soal' => '<p>Halo, aku soal</p>',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => true,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('soals',[
        'soal' => '<p>Halo, aku soal</p>',
        'type' => 'pg',
    ]);
});

it('Should return 403 if uploads into other user\'s ujian', function(){

    $user = User::factory()
        ->has(Guru::factory()->count(1))
        ->create();
    
    $user->assignRole('guru');

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => 2,
            'soal' => '<p>Halo, aku soal</p>',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => true,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertForbidden();
});

it('Should return 422 if soal is empty', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => 2,
            'soal' => '',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => true,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.soal.0','Harus diisi');
});

it('Should return 422 if bobot is not a number', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => 'dshfkjdsf',
            'soal' => '<p>Halo, aku soal</p>',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => true,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.bobot.0','Harus berupa angka');
});

it('Should return 422 if bobot is less than 0', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => -1,
            'soal' => '<p>Halo, aku soal</p>',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => true,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.bobot.0','Harus lebih dari 0');
});

it('Should have at least 1 correct answer for multiple choice', function(){

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->user->getAccessToken(),
        ])
        ->postJson($this->endpointUrl, [
            'ujianId' => $this->ujian->id,
            'bobot' => 2,
            'soal' => '<p>Halo, aku soal</p>',
            'type' => 'pg',
            'jawabans' => [
                [
                    'content' => '<p>Halo, aku jawaban A</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban B</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban C</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban D</p>',
                    'isCorrect' => false,
                ],
                [
                    'content' => '<p>Halo, aku jawaban E</p>',
                    'isCorrect' => false,
                ],
            ]
        ]);
    
    $response->assertUnprocessable();
    $response->assertJsonPath('errors.jawabans.0','Setidaknya harus ada 1 jawaban yang benar');
});
