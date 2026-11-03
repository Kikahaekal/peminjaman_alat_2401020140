<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Data Peminjaman Alat</title> 
</head> 
<body> 
    <h1>Data Peminjaman Alat</h1> 
 
    @if (session('berhasil')) 
        <p>{{ session('berhasil') }}</p> 
    @endif 
    @if (session('gagal')) 
        <p>{{ session('gagal') }}</p> 
    @endif 
 
    @if ($errors->any()) 
        <ul> 
            @foreach ($errors->all() as $error) 
                <li>{{ $error }}</li> 
            @endforeach 
        </ul> 
    @endif 
 
    <h2>Tambah Peminjaman</h2> 
    <form action="{{ route('peminjaman.store') }}" method="POST"> 
        @csrf 
        <p>Kode Peminjaman<br><input type="text" name="kode_peminjaman" value="{{ old('kode_peminjaman') }}"></p> 
        <p>Nama Peminjam<br><input type="text" name="nama_peminjam" value="{{ old('nama_peminjam') }}"></p> 
        <p>Nomor Identitas<br><input type="text" name="nomor_identitas" value="{{ old('nomor_identitas') }}"></p> 
        <p>Jumlah Alat<br><input type="number" name="jumlah_alat" value="{{ old('jumlah_alat') }}"></p> 
        <p>Alat<br> 
            <select name="alat_id"> 
                <option value="">-- Pilih Alat --</option> 
                @foreach ($daftarAlat as $alat) 
                    <option value="{{ $alat['id'] }}" 
                        @selected(old('alat_id') == $alat['id'])> 
                        {{ $alat['nama_alat'] }} 
                    </option> 
                @endforeach 
            </select> 
        </p> 
        <button type="submit">Tambah Data</button> 
    </form> 
 
    <h2>Pencarian dan Filter</h2> 
    <form action="{{ route('peminjaman.index') }}" method="GET"> 
        <p>Kata Kunci<br> 
            <input type="text" name="q" value="{{ $kataKunci }}"> 
        </p> 
        <p>Alat<br> 
            <select name="alat_id"> 
                <option value="">-- Semua Alat --</option> 
                @foreach ($daftarAlat as $alat) 
                    <option value="{{ $alat['id'] }}" 
                        @selected((string) $filterAlat === (string) $alat['id'])> 
                        {{ $alat['nama_alat'] }} 
                    </option> 
                @endforeach 
            </select> 
        </p> 
        <button type="submit">Cari</button> 
        <a href="{{ route('peminjaman.index') }}">Reset</a> 
    </form> 
    <h2>Daftar Peminjaman</h2> 
        <table border="1" cellpadding="6" cellspacing="0"> 
            <tr> 
                <th>Kode</th>
                <th>Nama Peminjam</th>
                <th>Nomor Identitas</th> 
                <th>Jumlah</th>
                <th>Status</th>
                <th>Alat</th>
                <th>Aksi</th> 
            </tr> 
            @forelse ($daftarPeminjaman as $peminjaman) 
                <tr> 
                    <td>{{ $peminjaman['kode_peminjaman'] }}</td> 
                    <td>{{ $peminjaman['nama_peminjam'] }}</td> 
                    <td>{{ $peminjaman['nomor_identitas'] }}</td> 
                    <td>{{ $peminjaman['jumlah_alat'] }}</td> 
                    <td>{{ statusJumlah((int) $peminjaman['jumlah_alat']) }}</td> 
                    <td>{{ $peminjaman['nama_alat'] }}</td> 
                    <td> 
                    <a href="{{ route('peminjaman.edit', $peminjaman['id']) }}"> 
                    Ubah 
                    </a> 
                        <form action="{{ route('peminjaman.destroy', $peminjaman['id']) }}" 
                            method="POST"> 
                            @csrf 
                            @method('DELETE') 
                            <button type="submit" 
                            onclick="return confirm('Hapus data ini?')"> 
                            Hapus 
                            </button> 
                        </form> 
                    </td> 
                </tr> 
                @empty 
                <tr><td colspan="7">Data tidak ditemukan.</td></tr> 
            @endforelse 
        </table> 
    </body> 
</html>