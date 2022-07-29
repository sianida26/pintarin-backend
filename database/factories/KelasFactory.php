<?php

namespace Database\Factories;

use App\Models\Matpel;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelas>
 */
class KelasFactory extends Factory
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
            'matpeL_id' => Matpel::all()->random()->id,
            // 'guru_id' => Guru::factory()->create(),
        ];
    }
}
