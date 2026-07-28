<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** الأنواع التي تظهر للمواطن في بوابة رأي المواطن. */
    private const PUBLIC_TYPES = [
        'فرع توثيق',
        'مأمورية شهر',
        'فرع توثيق ومأمورية شهر (مدمج)',
        'سجل عيني',
        'توثيق وسجل عيني',
        'مأمورية شهر وسجل عيني',
        'توثيق ومأمورية شهر وسجل عيني',
    ];

    public function up(): void
    {
        Schema::table('office_types', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('name');
        });

        DB::table('office_types')
            ->whereIn('name', self::PUBLIC_TYPES)
            ->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('office_types', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
