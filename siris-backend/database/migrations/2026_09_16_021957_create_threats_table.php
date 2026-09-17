<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threats', function (Blueprint $table) {
            $table->id();

            $table->string('kode_ancaman')->unique();

            $table->string('nama_ancaman');

            $table->text('deskripsi')->nullable();

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('nama_ancaman');
            $table->index('asset_id');
            $table->index('dibuat_oleh');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threats');
    }
};