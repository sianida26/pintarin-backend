<?php

namespace Database\Seeders;

use App\Models\Matpel;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatpelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $matpels = collect([
            'Matematika',
            'Bahasa Indonesia',
            'Kimia',
            'Fisika',
            'Biologi',
            'Ekonomi',
            'Sosiologi',
            'Geografi',
            'Sejarah',
            'Bahasa Inggris',
            'Bahasa Jerman',
            'Bahasa Jepang',
            'Prakarya',
            'TIK',
            'Olahraga',
            'Agama',
            'PKn',
            'Bahasa Jawa',
            'Antropologi',
            'Seni Budaya',
            'BTQ',
            'Sastra Indonesia',
            'Sastra Inggris',
        ]);

        $created = 0;

        $matpels->each(function($matpel) use (&$created){
            if (Matpel::where([ 'name' => $matpel ])->exists()) return;

            Matpel::create(['name' => $matpel]);
            $created++;
        });

        $this->command->info($created . ' matpels created, ' . $matpels->count() - $created . ' skipped');
    }
}
