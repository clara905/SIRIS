<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('kode_aset')->unique();

            $table->string('nama_aset');

            $table->enum('kategori', [
                'physical',
                'software',
                'digital',
            ]);

            $table->foreignId('bidang_id')
                ->nullable()
                ->constrained('bidangs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sub_bidang_id')
                ->nullable()
                ->constrained('sub_bidangs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('satker_id')
                ->nullable()
                ->constrained('satkers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('deskripsi')->nullable();

            $table->unsignedTinyInteger('nilai_kekritisan')
                ->nullable();

            $table->timestamps();

            $table->index('nama_aset');
            $table->index('kategori');
            $table->index('bidang_id');
            $table->index('sub_bidang_id');
            $table->index('satker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};