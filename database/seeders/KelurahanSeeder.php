<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\Puskesmas;
use Illuminate\Database\Seeder;

class KelurahanSeeder extends Seeder
{
    public const KELURAHANS = [
        1 => 'PAYOLANSEK',
        2 => 'BULBA',
        3 => 'PAKAN SINAYAN',
        4 => 'KUBU GADANG',
        5 => 'KOTO TANGAH',
        6 => 'TALANG',
    ];

    public function run(): void
    {
        $puskesmas = Puskesmas::firstOrCreate(
            ['name' => 'PAYOLANSEK'],
            ['city' => 'PAYAKUMBUH']
        );

        foreach (self::KELURAHANS as $order => $name) {
            Kelurahan::updateOrCreate(
                [
                    'puskesmas_id' => $puskesmas->id,
                    'name' => $name,
                ],
                [
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
