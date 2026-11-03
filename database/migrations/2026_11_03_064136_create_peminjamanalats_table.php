<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peminjamanalats', function (Blueprint $table) {
            $table->id();
            $table->char('kode_peminjam', length: 10)->unique();
            $table->string('nama_peminjam', length: 100);
            $table->string('nomor_identitas', length: 30)->unique();
            $table->tinyInteger('jumlah_alat');
            $table->foreignId('alat_id')->constrained('alats')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjamanalats');
    }
};
