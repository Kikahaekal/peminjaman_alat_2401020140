<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (! function_exists('statusJumlah')) {
    function statusJumlah(int $jumlah): string
    {
        return $jumlah >= 3 ? 'Banyak' : 'Sedikit';
    }
}

if (! function_exists('siapkanSkemaPeminjaman')) {
    function siapkanSkemaPeminjaman(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS alat (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nama_alat VARCHAR(100) NOT NULL UNIQUE
                )'
            );
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS peminjaman_alat (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kode_peminjaman CHAR(10) NOT NULL UNIQUE,
                    nama_peminjam VARCHAR(100) NOT NULL,
                    nomor_identitas VARCHAR(30) NOT NULL UNIQUE,
                    jumlah_alat INTEGER NOT NULL CHECK (jumlah_alat BETWEEN 1 AND 10),
                    alat_id INTEGER NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (alat_id) REFERENCES alat(id)
                )'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS alat (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    nama_alat VARCHAR(100) NOT NULL UNIQUE
                ) ENGINE=InnoDB'
            );
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS peminjaman_alat (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    kode_peminjaman CHAR(10) NOT NULL UNIQUE,
                    nama_peminjam VARCHAR(100) NOT NULL,
                    nomor_identitas VARCHAR(30) NOT NULL UNIQUE,
                    jumlah_alat TINYINT UNSIGNED NOT NULL CHECK (jumlah_alat BETWEEN 1 AND 10),
                    alat_id BIGINT UNSIGNED NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    CONSTRAINT peminjaman_alat_alat_id_foreign
                        FOREIGN KEY (alat_id) REFERENCES alat(id)
                ) ENGINE=InnoDB'
            );
        }

        $cariAlat = $pdo->prepare('SELECT id FROM alat WHERE nama_alat = :nama');
        $simpanAlat = $pdo->prepare('INSERT INTO alat (nama_alat) VALUES (:nama)');
        $alatIds = [];

        foreach (['Kamera', 'Proyektor'] as $namaAlat) {
            $cariAlat->execute(['nama' => $namaAlat]);
            $id = $cariAlat->fetchColumn();

            if ($id === false) {
                $simpanAlat->execute(['nama' => $namaAlat]);
                $id = $pdo->lastInsertId();
            }

            $alatIds[$namaAlat] = (int) $id;
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM peminjaman_alat')->fetchColumn() === 0) {
            $simpanPeminjaman = $pdo->prepare(
                'INSERT INTO peminjaman_alat
                    (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id, created_at, updated_at)
                 VALUES
                    (:kode, :nama, :identitas, :jumlah, :alat_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );

            foreach ([
                ['PM00000001', 'Rina Kurnia', 'MHS-2201001', 2, 'Kamera'],
                ['PM00000002', 'Dedi Pranata', 'MHS-2201002', 1, 'Proyektor'],
                ['PM00000003', 'Mira Ananda', 'MHS-2201003', 3, 'Kamera'],
            ] as [$kode, $nama, $identitas, $jumlah, $namaAlat]) {
                $simpanPeminjaman->execute([
                    'kode' => $kode,
                    'nama' => $nama,
                    'identitas' => $identitas,
                    'jumlah' => $jumlah,
                    'alat_id' => $alatIds[$namaAlat],
                ]);
            }
        }
    }
}

$tampilkanPeminjaman = function (Request $request) {
    $kataKunci = trim((string) $request->query('q', ''));
    $filterAlat = trim((string) $request->query('alat_id', ''));

    try {
        $pdo = DB::connection()->getPdo();
        siapkanSkemaPeminjaman($pdo);

        $sql = 'SELECT p.id, p.kode_peminjaman, p.nama_peminjam,
                       p.nomor_identitas, p.jumlah_alat, p.alat_id,
                       a.nama_alat
                FROM peminjaman_alat AS p
                JOIN alat AS a ON a.id = p.alat_id
                WHERE 1 = 1';
        $parameter = [];

        if ($kataKunci !== '') {
            $sql .= ' AND (p.kode_peminjaman LIKE :kode
                      OR p.nama_peminjam LIKE :nama
                      OR p.nomor_identitas LIKE :identitas)';
            $nilaiPencarian = '%'.$kataKunci.'%';
            $parameter['kode'] = $nilaiPencarian;
            $parameter['nama'] = $nilaiPencarian;
            $parameter['identitas'] = $nilaiPencarian;
        }

        if (filter_var($filterAlat, FILTER_VALIDATE_INT) !== false && (int) $filterAlat > 0) {
            $sql .= ' AND p.alat_id = :alat_id';
            $parameter['alat_id'] = (int) $filterAlat;
        }

        $sql .= ' ORDER BY p.kode_peminjaman';
        $ambilPeminjaman = $pdo->prepare($sql);
        $ambilPeminjaman->execute($parameter);
        $daftarPeminjaman = $ambilPeminjaman->fetchAll(PDO::FETCH_ASSOC);

        $ambilAlat = $pdo->prepare('SELECT id, nama_alat FROM alat ORDER BY nama_alat');
        $ambilAlat->execute();
        $daftarAlat = $ambilAlat->fetchAll(PDO::FETCH_ASSOC);

        return view('peminjaman', compact(
            'daftarPeminjaman',
            'daftarAlat',
            'kataKunci',
            'filterAlat'
        ));
    } catch (Throwable $error) {
        report($error);

        return response(
            'Data peminjaman tidak dapat dibaca. Periksa koneksi dan konfigurasi database.',
            500
        );
    }
};

Route::get('/', $tampilkanPeminjaman);
Route::get('/peminjaman', $tampilkanPeminjaman)->name('peminjaman.index');

Route::post('/peminjaman', function (Request $request) {
    try {
        $pdo = DB::connection()->getPdo();
        siapkanSkemaPeminjaman($pdo);
    } catch (Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan konfigurasi database.'
        );
    }

    $input = $request->only([
        'kode_peminjaman',
        'nama_peminjam',
        'nomor_identitas',
        'jumlah_alat',
        'alat_id',
    ]);

    foreach (['kode_peminjaman', 'nama_peminjam', 'nomor_identitas'] as $field) {
        $input[$field] = strip_tags(trim((string) ($input[$field] ?? '')));
    }

    $request->merge($input);
    $data = $request->validate([
        'kode_peminjaman' => ['required', 'string', 'size:10', 'regex:/^[A-Za-z0-9]+$/'],
        'nama_peminjam' => ['required', 'string', 'min:3', 'max:100'],
        'nomor_identitas' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
        'jumlah_alat' => ['required', 'integer', 'min:1', 'max:10'],
        'alat_id' => ['required', 'integer', 'exists:alat,id'],
    ], [
        'kode_peminjaman.size' => 'Kode peminjaman harus tepat 10 karakter.',
        'kode_peminjaman.regex' => 'Kode peminjaman hanya boleh berisi huruf dan angka.',
        'nama_peminjam.min' => 'Nama peminjam minimal 3 karakter.',
        'nomor_identitas.min' => 'Nomor identitas minimal 5 karakter.',
        'nomor_identitas.regex' => 'Nomor identitas hanya boleh berisi huruf, angka, dan tanda hubung.',
        'jumlah_alat.integer' => 'Jumlah alat harus berupa bilangan bulat.',
        'jumlah_alat.min' => 'Jumlah alat minimal 1.',
        'jumlah_alat.max' => 'Jumlah alat maksimal 10.',
        'alat_id.exists' => 'Alat yang dipilih tidak tersedia.',
    ]);

    try {
        $cekDuplikat = $pdo->prepare(
            'SELECT COUNT(*) FROM peminjaman_alat
             WHERE kode_peminjaman = :kode OR nomor_identitas = :identitas'
        );
        $cekDuplikat->execute([
            'kode' => $data['kode_peminjaman'],
            'identitas' => $data['nomor_identitas'],
        ]);

        if ((int) $cekDuplikat->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'Kode peminjaman atau nomor identitas sudah digunakan.'
            );
        }

        $simpan = $pdo->prepare(
            'INSERT INTO peminjaman_alat
                (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id, created_at, updated_at)
             VALUES
                (:kode_peminjaman, :nama_peminjam, :nomor_identitas, :jumlah_alat, :alat_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $simpan->execute($data);

        return redirect()->route('peminjaman.index')->with(
            'berhasil',
            'Data peminjaman berhasil ditambahkan.'
        );
    } catch (Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data yang dimasukkan.'
        );
    }
})->name('peminjaman.store');

// Route::get('/peminjaman/{id}/edit', function (int $id) {
//     try {
//         $pdo = DB::connection()->getPdo();
//         siapkanSkemaPeminjaman($pdo);

//         $ambilPeminjaman = $pdo->prepare(
//             'SELECT id, kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id
//              FROM peminjaman_alat WHERE id = :id'
//         );
//         $ambilPeminjaman->execute(['id' => $id]);
//         $dataEdit = $ambilPeminjaman->fetch(PDO::FETCH_ASSOC);

//         if (! $dataEdit) {
//             return redirect()->route('peminjaman.index')->with(
//                 'gagal',
//                 'Data peminjaman tidak ditemukan.'
//             );
//         }

//         $ambilAlat = $pdo->prepare('SELECT id, nama_alat FROM alat ORDER BY nama_alat');
//         $ambilAlat->execute();
//         $daftarAlat = $ambilAlat->fetchAll(PDO::FETCH_ASSOC);

//         $peminjaman = $dataEdit;

//         return view('peminjaman-edit', compact('peminjaman', 'daftarAlat'));
//     } catch (Throwable $error) {
//         report($error);

//         return redirect()->route('peminjaman.index')->with(
//             'gagal',
//             'Data gagal dibuka untuk diubah. Periksa koneksi database.'
//         );
//     }
// })->name('peminjaman.edit');

// Route::put('/peminjaman/{id}', function (Request $request, int $id) {
//     try {
//         $pdo = DB::connection()->getPdo();
//         siapkanSkemaPeminjaman($pdo);
//     } catch (Throwable $error) {
//         report($error);

//         return back()->withInput()->with(
//             'gagal',
//             'Data gagal diperbarui. Periksa koneksi dan konfigurasi database.'
//         );
//     }

//     $input = $request->only([
//         'kode_peminjaman',
//         'nama_peminjam',
//         'nomor_identitas',
//         'jumlah_alat',
//         'alat_id',
//     ]);

//     foreach (['kode_peminjaman', 'nama_peminjam', 'nomor_identitas'] as $field) {
//         $input[$field] = strip_tags(trim((string) ($input[$field] ?? '')));
//     }

//     $request->merge($input);
//     $data = $request->validate([
//         'kode_peminjaman' => ['required', 'string', 'size:10', 'regex:/^[A-Za-z0-9]+$/'],
//         'nama_peminjam' => ['required', 'string', 'min:3', 'max:100'],
//         'nomor_identitas' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
//         'jumlah_alat' => ['required', 'integer', 'min:1', 'max:10'],
//         'alat_id' => ['required', 'integer', 'exists:alat,id'],
//     ], [
//         'kode_peminjaman.size' => 'Kode peminjaman harus tepat 10 karakter.',
//         'kode_peminjaman.regex' => 'Kode peminjaman hanya boleh berisi huruf dan angka.',
//         'nama_peminjam.min' => 'Nama peminjam minimal 3 karakter.',
//         'nomor_identitas.min' => 'Nomor identitas minimal 5 karakter.',
//         'nomor_identitas.regex' => 'Nomor identitas hanya boleh berisi huruf, angka, dan tanda hubung.',
//         'jumlah_alat.integer' => 'Jumlah alat harus berupa bilangan bulat.',
//         'jumlah_alat.min' => 'Jumlah alat minimal 1.',
//         'jumlah_alat.max' => 'Jumlah alat maksimal 10.',
//         'alat_id.exists' => 'Alat yang dipilih tidak tersedia.',
//     ]);

//     try {
//         $cekData = $pdo->prepare('SELECT COUNT(*) FROM peminjaman_alat WHERE id = :id');
//         $cekData->execute(['id' => $id]);

//         if ((int) $cekData->fetchColumn() === 0) {
//             return redirect()->route('peminjaman.index')->with(
//                 'gagal',
//                 'Data peminjaman tidak ditemukan.'
//             );
//         }

//         $cekDuplikat = $pdo->prepare(
//             'SELECT COUNT(*) FROM peminjaman_alat
//              WHERE (kode_peminjaman = :kode OR nomor_identitas = :identitas)
//                AND id <> :id'
//         );
//         $cekDuplikat->execute([
//             'kode' => $data['kode_peminjaman'],
//             'identitas' => $data['nomor_identitas'],
//             'id' => $id,
//         ]);

//         if ((int) $cekDuplikat->fetchColumn() > 0) {
//             return back()->withInput()->with(
//                 'gagal',
//                 'Kode peminjaman atau nomor identitas sudah digunakan data lain.'
//             );
//         }

//         $ubah = $pdo->prepare(
//             'UPDATE peminjaman_alat
//              SET kode_peminjaman = :kode_peminjaman,
//                  nama_peminjam = :nama_peminjam,
//                  nomor_identitas = :nomor_identitas,
//                  jumlah_alat = :jumlah_alat,
//                  alat_id = :alat_id,
//                  updated_at = CURRENT_TIMESTAMP
//              WHERE id = :id'
//         );
//         $ubah->execute([...$data, 'id' => $id]);

//         return redirect()->route('peminjaman.index')->with(
//             'berhasil',
//             'Data peminjaman berhasil diperbarui.'
//         );
//     } catch (Throwable $error) {
//         report($error);

//         return back()->withInput()->with(
//             'gagal',
//             'Data gagal diperbarui. Periksa koneksi dan data yang dimasukkan.'
//         );
//     }
// })->name('peminjaman.update');

// Route::delete('/peminjaman/{id}', function (int $id) {
//     try {
//         $pdo = DB::connection()->getPdo();
//         siapkanSkemaPeminjaman($pdo);

//         $hapus = $pdo->prepare('DELETE FROM peminjaman_alat WHERE id = :id');
//         $hapus->execute(['id' => $id]);

//         if ($hapus->rowCount() === 0) {
//             return redirect()->route('peminjaman.index')->with(
//                 'gagal',
//                 'Data peminjaman tidak ditemukan.'
//             );
//         }

//         return redirect()->route('peminjaman.index')->with(
//             'berhasil',
//             'Data peminjaman berhasil dihapus.'
//         );
//     } catch (Throwable $error) {
//         report($error);

//         return redirect()->route('peminjaman.index')->with(
//             'gagal',
//             'Data gagal dihapus. Periksa koneksi database.'
//         );
//     }
// })->name('peminjaman.destroy');