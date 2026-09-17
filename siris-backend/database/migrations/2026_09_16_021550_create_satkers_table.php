<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satkers', function (Blueprint $table) {
            $table->id();
            $table->string('kode_satker', 100)->unique();
            $table->string('nama_satker');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satkers');
    }
};