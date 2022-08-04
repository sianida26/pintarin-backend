<?php

namespace Database\Factories;

use App\Models\Ujian;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Soal>
 */
class SoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {

        $jawabans = collect([
            [
                'id' => 0,
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'id' => 1,
                'content' => $this->faker->sentence(7),
                'isCorrect' => true,
            ],
            [
                'id' => 2,
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'id' => 3,
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'id' => 4,
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
        ]);

        return [
            'soal' => $this->faker->sentence(30),
            'bobot' => 3,
            'type' => 'pg',
            'answers' => $jawabans,
            'ujian_id' => Ujian::factory(),
        ];
    }

    public function pgk(){
        return $this->state(function(array $attributes){
            $jawabans = collect([
                [
                    'id' => 0,
                    'content' => $this->faker->sentence(7),
                    'isCorrect' => false,
                ],
                [
                    'id' => 1,
                    'content' => $this->faker->sentence(7),
                    'isCorrect' => true,
                ],
                [
                    'id' => 2,
                    'content' => $this->faker->sentence(7),
                    'isCorrect' => false,
                ],
                [
                    'id' => 3,
                    'content' => $this->faker->sentence(7),
                    'isCorrect' => true,
                ],
                [
                    'id' => 4,
                    'content' => $this->faker->sentence(7),
                    'isCorrect' => false,
                ],
            ]);
            return [
                'type' => 'pgk',
                'answers' => $jawabans,
            ];
        });
    }

    public function isian(){

        return $this->state(fn (array $attributes) => [
            'type' => 'isian',
            'answers' => [$this->faker->sentence(7)],
        ]);
    }

    public function uraian(){

        return $this->state(fn (array $attributes) => [
            'type' => 'uraian',
            'answers' => [$this->faker->sentence(30)],
        ]);
    }
}
