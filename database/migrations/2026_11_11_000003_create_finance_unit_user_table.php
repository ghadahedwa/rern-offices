<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط المستخدم بالوحدات الحسابية (نمط `governorate_user`): يرى ويُدخل ما رُبط به وحده.
 * ⚠️ القائمة الفارغة = لا وحدة — فورم المستخدم يمنع حفظها لمن دوره يستلزم الوحدات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_unit_user', function (Blueprint $table) {
            $table->foreignId('finance_unit_id')->constrained('finance_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['finance_unit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_unit_user');
    }
};
