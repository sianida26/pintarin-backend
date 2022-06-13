<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'admin',
            'guru',
            'guru profesional',
            'guru komunitas',
            'siswa',
        ];

        $countCreated = 0;

        foreach ($roles as $role) {
            //create role if not exists
            if (!Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
                $countCreated++;
            }
        }

        $this->command->info("$countCreated roles created");
    }
}
