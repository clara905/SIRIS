<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->renameColumn('pengadaan', 'pengadaan_lama');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->date('pengadaan')->nullable();
        });
        DB::table('assets')->whereNotNull('pengadaan_lama')->orderBy('id')->chunkById(200, function ($assets): void {
            foreach ($assets as $asset) {
                $value = trim($asset->pengadaan_lama);
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if ($date && $date->format('Y-m-d') === $value && $value >= '1000-01-01' && $value <= '9999-12-31') {
                    DB::table('assets')->where('id', $asset->id)->update(['pengadaan' => $value]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('assets')->whereNotNull('pengadaan')->update(['pengadaan_lama' => DB::raw('pengadaan')]);
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('pengadaan');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->renameColumn('pengadaan_lama', 'pengadaan');
        });
    }
};
