<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('microfilm_option_id')->nullable()->constrained('microfilm_options')->nullOnDelete();
            $table->foreignId('disabilities_access_id')->nullable()->constrained('disabilities_access')->nullOnDelete();
            $table->foreignId('fire_safety_id')->nullable()->constrained('fire_safety')->nullOnDelete();
            $table->foreignId('document_photocopying_service_id')->nullable()->constrained('documents_photocopying_service')->nullOnDelete();
            $table->foreignId('buffet_service_id')->nullable()->constrained('buffet_service')->nullOnDelete();
            $table->foreignId('cleanliness_contract_id')->nullable()->constrained('cleanliness_contracts')->nullOnDelete();
            $table->unsignedInteger('office_area')->nullable();
            $table->string('district_court')->nullable();
            $table->string('Braille_sign_device')->nullable();
            $table->string('queue_management_system')->nullable();
            $table->unsignedInteger('payment_machine_count')->nullable();
            $table->unsignedInteger('computers_count')->nullable();
            $table->unsignedInteger('scanners_count')->nullable();
            $table->unsignedInteger('printers_count')->nullable();
            $table->unsignedInteger('fingerprints_count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('microfilm_option_id');
            $table->dropConstrainedForeignId('disabilities_access_id');
            $table->dropConstrainedForeignId('fire_safety_id');
            $table->dropConstrainedForeignId('document_photocopying_service_id');
            $table->dropConstrainedForeignId('buffet_service_id');
            $table->dropConstrainedForeignId('cleanliness_contract_id');
        });
    }
};
