<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_treatments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('risk_assessment_id')
                ->constrained('risk_assessments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('strategi', [
                'mitigasi',
                'transfer',
                'hindari',
                'terima',
            ]);

            $table->foreignId('pic')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('target_selesai')->nullable();

            $table->enum('status', [
                'belum',
                'proses',
                'selesai',
            ])->default('belum');

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('risk_assessment_id');
            $table->index('pic');
            $table->index('status');
            $table->index('target_selesai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_treatments');
    }
};