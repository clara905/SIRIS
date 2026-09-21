<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
        });
        Schema::create('risk_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('bidang_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_bidang_id')->constrained()->restrictOnDelete();
            $table->foreignId('satker_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->json('new_vulnerabilities')->nullable();
            $table->text('catatan')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->json('timeline');
            $table->timestamps();
        });
        Schema::create('risk_submission_threat', function (Blueprint $table) {
            $table->foreignId('risk_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threat_id')->constrained()->restrictOnDelete();
            $table->primary(['risk_submission_id', 'threat_id'], 'submission_threat_pk');
        });
        Schema::create('risk_submission_vulnerability', function (Blueprint $table) {
            $table->foreignId('risk_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vulnerability_id')->constrained()->restrictOnDelete();
            $table->primary(['risk_submission_id', 'vulnerability_id'], 'submission_vulnerability_pk');
        });
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->foreignId('risk_submission_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
        Schema::table('risk_treatments', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });
        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('url');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
        Schema::table('risk_treatments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('risk_submission_id');
        });
        Schema::dropIfExists('risk_submission_vulnerability');
        Schema::dropIfExists('risk_submission_threat');
        Schema::dropIfExists('risk_submissions');
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('status');
        });
    }
};
