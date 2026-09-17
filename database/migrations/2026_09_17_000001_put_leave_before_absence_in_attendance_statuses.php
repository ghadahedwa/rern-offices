<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «إجازة» قبل «غائب» في الترتيب (طلب المستخدمة ٢٠٢٦-٠٩-١٧) — أدوات شبكة الحضور وأعمدتها
 * وشاشة الحالات تقرأ الترتيب نفسه.
 *
 * ⚠️ **لا يُبدَّل إلا الترتيب المزروع كما هو** (غائب ٢ · إجازة ٣): الترتيب حقلٌ يعدّله المدير
 *    من شاشة الحالات، وهجرةٌ تكتب فوقه تمحو اختياره بلا أثر.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->swap(from: [2, 3], to: [3, 2]);
    }

    public function down(): void
    {
        $this->swap(from: [3, 2], to: [2, 3]);
    }

    /** @param array{0:int,1:int} $from ترتيب [غائب، إجازة] المتوقَّع قبل التبديل */
    private function swap(array $from, array $to): void
    {
        $absent = DB::table('attendance_statuses')->where('is_system', true)->where('name', 'غائب')->first();
        $leave  = DB::table('attendance_statuses')->where('is_system', true)->where('name', 'إجازة')->first();

        if (! $absent || ! $leave || (int) $absent->order !== $from[0] || (int) $leave->order !== $from[1]) {
            return;
        }

        DB::table('attendance_statuses')->where('id', $absent->id)->update(['order' => $to[0]]);
        DB::table('attendance_statuses')->where('id', $leave->id)->update(['order' => $to[1]]);
    }
};
