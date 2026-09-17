<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_bidangs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bidang_id')
                ->constrained('bidangs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nama_sub_bidang');
            $table->string('kode_sub_bidang')->nullable()->unique();

            $table->timestamps();

            $table->index('bidang_id');
            $table->index('nama_sub_bidang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_bidangs');
    }
};