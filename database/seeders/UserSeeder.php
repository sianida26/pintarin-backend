<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Guru Komunitas
        if (User::where('email', 'gurukomunitas@test.com')->exists()) $this->command->warn('Guru Komunitas already created');
        else {
            $guruKomunitas = User::create([
                'email' => 'gurukomunitas@test.com',
                'name' => "Coba Guru Komunitas",
                'password' => Hash::make("landmark"),
                'TTL' => "__testing__",
            ]);
            $guruKomunitas->guru()->create([]);
            $guruKomunitas->assignRole('guru');
            $guruKomunitas->assignRole('guru komunitas');
            $this->command->info('Guru Komunitas created');
        }

        //Guru Profesional
        if (User::where('email', 'guru@test.com')->exists()) $this->command->warn('Guru Profesional already created');
        else {
            $guruProfesional = User::create([
                'email' => 'guru@test.com',
                'name' => "Coba Guru Profesional",
                'password' => Hash::make("landmark"),
                'TTL' => "__testing__",
            ]);
            $guruProfesional->guru()->create([]);
            $guruProfesional->assignRole('guru');
            $guruProfesional->assignRole('guru profesional');
            $this->command->info('Guru Profesional created');
        }

        //Siswa
        if (User::where('email','siswa@test.com')->exists()) $this->command->warn('Siswa already created');
        else {
            $guruProfesional = User::create([
                'email' => 'siswa@test.com',
                'name' => "Coba Siswa",
                'password' => Hash::make("landmark"),
                'TTL' => "__testing__",
            ]);
            $guruProfesional->siswa()->create([]);
            $guruProfesional->assignRole('siswa');
            $this->command->info('Siswa created');
        }
    }
}
