<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المطالبات — كل سجل: محافظة + تاريخ + مبلغ
        Schema::create('governorate_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['governorate_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorate_demands');
    }
};
