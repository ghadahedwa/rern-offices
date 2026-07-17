<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الوارد (رأس المستند) — للمخزن الرئيسي فقط، مستند فيه صنف أو أكثر
        Schema::create('warehouse_incomings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('received_at');                                  // تاريخ الورود
            $table->string('supplier_name')->nullable();                  // المورد (اختياري)
            $table->string('attachment_path');                            // مرفق إجباري (صورة/PDF)
            $table->string('attachment_original_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_incomings');
    }
};
