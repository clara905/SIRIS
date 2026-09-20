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
        Schema::table('satkers', function (Blueprint $table) {
            $table->foreignId('bidang_id')->nullable()->constrained('bidangs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satkers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bidang_id');
        });
    }
};
