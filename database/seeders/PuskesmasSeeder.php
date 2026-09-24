<?php

namespace Database\Seeders;

use App\Models\Puskesmas;
use Illuminate\Database\Seeder;

class PuskesmasSeeder extends Seeder
{
    public function run(): void
    {
        Puskesmas::updateOrCreate(
            ['name' => 'PAYOLANSEK'],
            [
                'city' => 'PAYAKUMBUH',
            ]
        );
    }
}
