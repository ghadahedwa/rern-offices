<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قصر الدور على أنواع مقرات و/أو أوصاف مواقع بعينها (طلب العميل 2026-10-17: دور «حياة كريمة»).
 *
 * ⚠️ الجدولان الفارغان لدورٍ = لا قيد عليه — كل الأدوار القائمة تبقى كما هي.
 *    والقراءة كلها عبر `App\Support\OfficeScope` لا مباشرةً.
 * ⚠️ لذلك النوع/الوصف `restrictOnDelete` لا `cascade`: حذفُ آخر وصفٍ على الدور كان سيمسح صفّه
 *    فيصير الدور بلا قيد ويرى مقرات الجمهورية كلها. شاشتا الحذف تمنعان قبله برسالة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_type_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('office_type_id')->constrained('office_types')->restrictOnDelete();
            $table->primary(['role_id', 'office_type_id']);
        });

        Schema::create('location_description_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('location_description_id')->constrained('location_descriptions')->restrictOnDelete();
            $table->primary(['role_id', 'location_description_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_description_role');
        Schema::dropIfExists('office_type_role');
    }
};
