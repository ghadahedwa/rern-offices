<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_rejected_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('type');                // rating | suggestion
            $table->string('national_id', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason');              // duplicate_window | rate_limit | honeypot | invalid
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['national_id', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_rejected_attempts');
    }
};
