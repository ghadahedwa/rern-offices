<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                       // اسم الصنف
            $table->foreignId('item_unit_id')->nullable()->constrained('item_units')->nullOnDelete();
            $table->unsignedInteger('min_stock')->nullable();             // الحد الأدنى (يُفحَص على المخزن الرئيسي فقط)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
