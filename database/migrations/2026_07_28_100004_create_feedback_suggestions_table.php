<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 100);
            $table->string('national_id', 14);   // مفتاح منع التكرار
            $table->string('phone', 20);
            $table->text('other_suggestion')->nullable(); // "اقتراح آخر" الحر

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['national_id', 'office_id']);
            $table->index('office_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_suggestions');
    }
};
