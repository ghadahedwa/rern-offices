<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('name');
            $table->string('supervising_counselor')->nullable();
            $table->date('established_at')->nullable();
            $table->string('type');                         // main, documentation_branch, registration_agency, merged, real_estate_register, administration
            $table->string('location_description')->nullable(); // regular, court, post, urban_community, club, investment, mall, orange, telecom
            $table->string('work_system')->nullable();      // advance_booking, regular
            $table->text('address')->nullable();
            $table->string('google_maps_link')->nullable();
            $table->string('floors_description')->nullable();
            $table->string('connection_type')->nullable();  // fiber, copper, wifi, safety_network

            // Step 3 — Assessments
            $table->unsignedInteger('avg_daily_transactions')->nullable();
            $table->string('contractual_status')->nullable();   // new_rent, old_rent, owned, allocated
            $table->string('structural_condition')->nullable(); // good, average, deteriorated_upgrade, deteriorated_replace
            $table->string('cleanliness_rating')->nullable();   // good, average, bad
            $table->string('working_hours')->nullable();        // morning_eve1, morning_eve1_eve2, evening_only
            $table->string('archive_rating')->nullable();       // excellent, good, average, bad
            $table->string('microfilm')->nullable();            // al_ahram, al_akhbar, none
            $table->string('disabilities_access')->nullable();  // equipped, feasible, not_feasible
            $table->string('fire_safety')->nullable();          // auto, extinguishers_ok, extinguishers_maintenance, none

            // Step 4 — Notes
            $table->text('negatives_and_solutions')->nullable();
            $table->text('development_proposals')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
