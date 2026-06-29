<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // المطالبات الملغاة — مبلغ مستقل بسبب، يُطرح من المديونية
    public function up(): void
    {
        Schema::create('governorate_cancelled_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['governorate_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorate_cancelled_demands');
    }
};
