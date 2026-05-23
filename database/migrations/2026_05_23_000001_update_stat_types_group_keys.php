<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // التاب 1: معاملات التوثيق (معاملات المكتب السنوية - id=1)
        DB::table('stat_types')->where('id', 1)->update(['group_key' => 'transactions']);

        // التاب 2: إحصائية النماذج والحوافظ (id=2,3)
        DB::table('stat_types')->whereIn('id', [2, 3])->update(['group_key' => 'forms_folders']);

        // التاب 3: طلبات الشهر (id=5)
        DB::table('stat_types')->where('id', 5)->update(['group_key' => 'shaher_requests']);

        // التاب 4: طلبات السجل (id=6)
        DB::table('stat_types')->where('id', 6)->update(['group_key' => 'registry_requests']);
    }

    public function down(): void
    {
        DB::table('stat_types')->whereIn('id', [1, 2, 3])->update(['group_key' => 'transactions_sales']);
        DB::table('stat_types')->whereIn('id', [5, 6])->update(['group_key' => 'requests']);
    }
};
