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
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'content' => $this->faker->sentence(7),
                'isCorrect' => true,
            ],
            [
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
            [
                'content' => $this->faker->sentence(7),
                'isCorrect' => false,
            ],
        ]);

        return [
            'soal' => $this->faker->sentence(30),
            'bobot' => 3,
            'type' => 'pg',
            'answers' => collect($jawabans)->toJson(),
            'ujian_id' => Ujian::factory(),
        ];
    }
}
