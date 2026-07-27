<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_ratings', function (Blueprint $table) {
            $table->id();
            // nullOnDelete + denormalized governorate_id: نحتفظ بالتقييم حتى لو حُذف المقر
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 100);
            $table->string('national_id', 14);   // مفتاح منع التكرار
            $table->string('phone', 20);

            $table->string('wait_time');          // under_15 | 15_30 | 30_60 | over_60
            $table->unsignedTinyInteger('rating_speed');
            $table->unsignedTinyInteger('rating_staff');
            $table->unsignedTinyInteger('rating_queue');
            $table->unsignedTinyInteger('rating_cleanliness');
            $table->unsignedTinyInteger('rating_clarity');
            $table->unsignedTinyInteger('rating_accessibility')->nullable(); // اختياري
            $table->unsignedTinyInteger('overall_rating');
            $table->text('notes')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['national_id', 'office_id']); // فحص قاعدة الأسبوعين
            $table->index('office_id');                  // تجميع النتائج
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_ratings');
    }
};
