<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\peminjamanalat;

class PeminjamanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $array = [
            [
                'kode_peminjam' => 'PM00000001',
                'nama_peminjam' => 'Rina Kurnia',
                'nomor_identitas' => 'MHS-2201001',
                'jumlah_alat' => 2,
                'alat_id' => 1,
            ],
            [
                'kode_peminjam' => 'PM00000002',
                'nama_peminjam' => 'Dedi Pranata',
                'nomor_identitas' => 'MHS-2201002',
                'jumlah_alat' => 1,
                'alat_id' => 2,
            ],
            [
                'kode_peminjam' => 'PM00000003',
                'nama_peminjam' => 'Mira Ananda',
                'nomor_identitas' => 'MHS-2201003',
                'jumlah_alat' => 3,
                'alat_id' => 1,
            ],
        ];

        foreach ($array as $value) {
            peminjamanalat::create([
                'kode_peminjam' => $value['kode_peminjam'],
                'nama_peminjam' => $value['nama_peminjam'],
                'nomor_identitas' => $value['nomor_identitas'],
                'jumlah_alat' => $value['jumlah_alat'],
                'alat_id' => $value['alat_id'],
            ]);
        }
    }
}
