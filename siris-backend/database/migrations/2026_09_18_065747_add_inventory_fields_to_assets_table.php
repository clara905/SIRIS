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
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedInteger('idx')->nullable()->unique();
            $table->string('kondisi', 100)->nullable();
            $table->string('merk')->nullable();
            $table->string('snumber')->nullable();
            $table->string('pengadaan')->nullable();
            $table->string('lokasi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropUnique(['idx']);
            $table->dropColumn(['idx', 'kondisi', 'merk', 'snumber', 'pengadaan', 'lokasi']);
        });
    }
};
