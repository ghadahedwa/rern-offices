<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // النقل (رأس المستند) — من مخزن مصدر إلى مخزن مستلم، مستند فيه صنف أو أكثر
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('transferred_at');                               // تاريخ الصرف
            $table->string('document_type')->nullable();                  // نوع المستند (إذن صرف/استمارة نقل عهدة)
            $table->string('attachment_path');                            // مرفق إجباري (صورة/PDF)
            $table->string('attachment_original_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
