<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('threat_id')
                ->constrained('threats')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('likelihood');

            $table->unsignedTinyInteger('impact');

            $table->unsignedTinyInteger('skor');

            $table->enum('level', [
                'rendah',
                'sedang',
                'tinggi',
                'sangat_tinggi',
                'ekstrem',
            ]);

            $table->foreignId('dinilai_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('tanggal_penilaian');

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('asset_id');
            $table->index('threat_id');
            $table->index('level');
            $table->index('dinilai_oleh');
            $table->index('tanggal_penilaian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};