<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sipela.test'],
            [
                'name' => 'Administrator Puskesmas',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas@sipela.test'],
            [
                'name' => 'Petugas Lansia',
                'password' => bcrypt('password'),
                'role' => 'petugas',
                'is_active' => true,
            ]
        );

        $this->call([
            PuskesmasSeeder::class,
            KelurahanSeeder::class,
        ]);
    }
}
