<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * حذف بيانات "النماذج والحوافظ" (forms_folders) لسنتي 2024 و 2025
     * من جميع المقرات بناءً على طلب العميل.
     * ملاحظة: عملية حذف نهائية لا يمكن التراجع عنها (down فارغة).
     */
    public function up(): void
    {
        $typeIds = DB::table('stat_types')
            ->where('group_key', 'forms_folders')
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return;
        }

        DB::table('office_statistics')
            ->whereIn('stat_type_id', $typeIds)
            ->whereIn('year', [2024, 2025])
            ->delete();
    }

    public function down(): void
    {
        // لا يمكن استرجاع البيانات المحذوفة.
    }
};
