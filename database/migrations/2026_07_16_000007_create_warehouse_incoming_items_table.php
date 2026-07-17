<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بنود الوارد (صنف + كمية) لكل مستند وارد
        Schema::create('warehouse_incoming_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_incoming_id')->constrained('warehouse_incomings')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_incoming_items');
    }
};
