<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المحصل من الطلبات — مبلغ شهري لكل محافظة
        Schema::create('governorate_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('value', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['governorate_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorate_claims');
    }
};
