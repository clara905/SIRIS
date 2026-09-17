<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('email')->unique();

            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('bidang_id')
                ->nullable()
                ->constrained('bidangs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sub_bidang_id')
                ->nullable()
                ->constrained('sub_bidangs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('satker_id')
                ->nullable()
                ->constrained('satkers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('fcm_token')->nullable();

            $table->boolean('is_active')->default(true);

            $table->rememberToken();

            $table->timestamps();

            $table->index('role_id');
            $table->index('bidang_id');
            $table->index('sub_bidang_id');
            $table->index('satker_id');
            $table->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};