<?php

namespace Database\Factories;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guru>
 */
class GuruFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'nip' => $this->faker->randomNumber(5, true),
            'nuptk' => $this->faker->randomNumber(8, true),
            'jabatan' => $this->faker->word(),
            'ttl' => $this->faker->words(5, true),
            'isMale' => $this->faker->boolean(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->e164PhoneNumber(),
            'statusKepegawaian' => $this->faker->randomElement(['Guru Tetap', 'Guru Honorer']),
            'pendidikanTerakhir' => $this->faker->randomElement(['S1', 'S2', 'S3']),
        ];
    }
}
