<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\Kelas;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ujian>
 */
class UjianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'category' => $this->faker->randomElement(['literasi','numerasi']),
            'isUjian' => $this->faker->boolean(),
            'kelas_id' => Kelas::factory(),
            // 'guru_id' => Guru::factory()->create(),
        ];
    }

    /**
     * Indicate that the ujian is latihan.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function latihan()
    {
        return $this->state(function (array $attributes) {
            return [
                'isUjian' => false,
            ];
        });
    }
}
