<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->date('date');                              // التاريخ
            $table->time('time');                              // الساعة
            $table->string('subject');                         // الموضوع
            $table->string('location')->nullable();            // المكان (نص حر)
            $table->string('concerned_party')->nullable();     // المعني بالاجتماع (نص حر)
            $table->string('concerned_party_title')->nullable(); // الصفة (نص حر)
            $table->text('result')->nullable();                // نتيجة الاجتماع
            $table->text('notes')->nullable();                 // ملاحظات (اختياري)
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
