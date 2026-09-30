<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * تقرير زيارة المستشار + صلاحيات التقريرين.
 *
 * تقرير المستشار **نسخة واحدة للمقر** كتقرير المفتش (قرار المستخدمة 2026-09-30):
 * كل زيارة تكتب فوق سابقتها — فمكانه أعمدة على `offices` بنفس بنود المفتش ببادئة
 * `counselor_`، لا جدول زيارات. المصدر الواحد للبنود: `App\Support\OfficeVisitReports`.
 *
 * الصلاحيات الأربع تُمنح لـ`super-admin`/`admin` وحدهما، **ولا تُوزَّع على الأدوار
 * القائمة** (قرار المستخدمة): التابان يختفيان عن باقي الأدوار حتى تُعلَّم من شاشة الأدوار.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'offices.inspector-report.view',
        'offices.inspector-report.edit',
        'offices.counselor-report.view',
        'offices.counselor-report.edit',
    ];

    private const TEXT_COLUMNS = [
        'counselor_office_needs',
        'counselor_negatives_and_solutions',
        'counselor_development_proposals',
    ];

    private const STRING_COLUMNS = [
        'counselor_cleanliness_rating',
        'counselor_archive_rating',
        'counselor_work_schedule_commitment',
        'counselor_citizen_treatment_commitment',
        'counselor_surveillance_cameras',
    ];

    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->date('counselor_visited_at')->nullable();
            $table->foreignId('counselor_structural_condition_id')->nullable()
                ->constrained('structural_conditions')->nullOnDelete();
            foreach (self::STRING_COLUMNS as $column) {
                $table->string($column)->nullable();
            }
            foreach (self::TEXT_COLUMNS as $column) {
                $table->text($column)->nullable();
            }
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach (['super-admin', 'admin'] as $roleName) {
                Role::where('name', $roleName)->first()?->givePermissionTo($permission);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::whereIn('name', self::PERMISSIONS)->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counselor_structural_condition_id');
            $table->dropColumn([
                'counselor_visited_at',
                ...self::STRING_COLUMNS,
                ...self::TEXT_COLUMNS,
            ]);
        });
    }
};
