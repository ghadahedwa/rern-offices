<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عمودان على المستخدم:
 *   correspondence_entity_id — الطرف الذي ينتمي إليه (نطاقه: أي دفتر يعمل عليه).
 *                              نظير «المحافظات» للمقرات — نطاق على المستخدم لا على الدور.
 *   job_title                — المسمّى الوظيفي المعروض. ⚠️ ليس الدور:
 *                              خالد وياسر لهما نفس الدور (رئيس جهة) ومسمّيان مختلفان.
 *                              ويُطبع في ختم الاعتماد، فهو بيانات رسمية لا زينة عرض.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('correspondence_entity_id')
                ->nullable()
                ->after('email')
                ->constrained('correspondence_entities')
                ->nullOnDelete();

            $table->string('job_title')->nullable()->after('correspondence_entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['correspondence_entity_id']);
            $table->dropColumn(['correspondence_entity_id', 'job_title']);
        });
    }
};
