<!DOCTYPE html> 
<html lang="id"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Ubah Peminjaman Alat</title> 
</head> 
<body> 
    <h1>Ubah Peminjaman Alat</h1> 
 
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
 
    <form action="{{ route('peminjaman.update', $peminjaman['id']) }}" 
          method="POST"> 
        @csrf 
        @method('PUT') 
        <p>Kode Peminjaman<br> 
            <input type="text" name="kode_peminjaman" 
                   value="{{ old('kode_peminjaman', $peminjaman['kode_peminjaman']) }}"> 
        </p> 
        <p>Nama Peminjam<br> 
            <input type="text" name="nama_peminjam" 
                   value="{{ old('nama_peminjam', $peminjaman['nama_peminjam']) }}"> 
        </p> 
        <p>Nomor Identitas<br> 
            <input type="text" name="nomor_identitas" 
                   value="{{ old('nomor_identitas', $peminjaman['nomor_identitas']) }}"> 
        </p> 
        <p>Jumlah Alat<br> 
            <input type="number" name="jumlah_alat" 
                   value="{{ old('jumlah_alat', $peminjaman['jumlah_alat']) }}"> 
        </p> 
        <p>Alat<br> 
            <select name="alat_id"> 
                @foreach ($daftarAlat as $alat) 
                    <option value="{{ $alat['id'] }}" 
                        @selected(old( 
                            'alat_id', 
                            $peminjaman['alat_id'] 
                        ) == $alat['id'])> 
                        {{ $alat['nama_alat'] }} 
                    </option> 
                @endforeach 
            </select> 
        </p> 
        <button type="submit">Simpan Perubahan</button> 
        <a href="{{ route('peminjaman.index') }}">Kembali</a> 
    </form> 
</body> 
</html>