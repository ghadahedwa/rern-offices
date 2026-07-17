<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سجل الحركات (Ledger) — صف لكل صنف في كل عملية، بالرصيد قبل وبعد
        Schema::create('warehouse_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->enum('type', ['opening', 'incoming', 'transfer_out', 'transfer_in']);
            $table->unsignedInteger('quantity');                          // موجب دائماً (الاتجاه من النوع)
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->nullableMorphs('reference');                         // يشير للوارد/النقل (reference_type + reference_id)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['warehouse_id', 'item_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');
    }
};
