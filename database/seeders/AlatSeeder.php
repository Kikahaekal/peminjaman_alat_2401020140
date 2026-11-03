<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\alat;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $array = [
            'Kamera',
            'Proyektor',
        ];

        foreach ($array as $value) {
            alat::create([
                'nama_alat' => $value,
            ]);
        }
    }
}
