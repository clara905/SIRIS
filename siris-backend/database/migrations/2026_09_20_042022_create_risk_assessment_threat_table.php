<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessment_threat', function (Blueprint $table) {
            $table->foreignId('risk_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threat_id')->constrained()->restrictOnDelete();
            $table->primary(['risk_assessment_id', 'threat_id']);
        });
        DB::table('risk_assessments')->orderBy('id')->chunkById(500, function ($risks) {
            DB::table('risk_assessment_threat')->insert($risks->map(fn ($risk): array => ['risk_assessment_id' => $risk->id, 'threat_id' => $risk->threat_id])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessment_threat');
    }
};
