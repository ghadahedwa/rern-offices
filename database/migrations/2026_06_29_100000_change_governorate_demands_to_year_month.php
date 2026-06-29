<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // المطالبة أصبحت شهرية (سنة + شهر) بدل تاريخ كامل — موازية للمحصل
    public function up(): void
    {
        Schema::dropIfExists('governorate_demands');

        Schema::create('governorate_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['governorate_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorate_demands');

        Schema::create('governorate_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['governorate_id', 'date']);
        });
    }
};
