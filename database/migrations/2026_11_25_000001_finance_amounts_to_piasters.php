<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * مبالغ الشئون المالية بالقروش (طلب المستخدمة 2026-11-25: «المبالغ تقبل قروش») — كانت جنيهاً صحيحاً.
 *
 *   - الأعمدة نفسها (`unsignedBigInteger`) تبقى، والقيم ×١٠٠: شهور الحسابات (`amount` · `income`) ·
 *     عناصر أبنية المحاكم · الأرصدة الافتتاحية.
 *   - ⚠️ **وسجلّ التصحيح والحذف** (`activity_log` باسم `finance`): قبل/بعد فيه مبالغ بالجنيه (`amount` ·
 *     `income` · `items`) — بلا تحويلها يُعرض التاريخ القديم مقسوماً على مئة.
 *   - في معاملة واحدة، والعكس (`down`) يقسم بالقسمة الصحيحة (تضيع القروش).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(fn () => $this->scale(fn (int $v) => $v * 100));
    }

    public function down(): void
    {
        DB::transaction(fn () => $this->scale(fn (int $v) => intdiv($v, 100)));
    }

    private function scale(callable $f): void
    {
        $factor = $f(100) === 10000 ? '* 100' : 'DIV 100';
        $sqlite = DB::getDriverName() === 'sqlite';
        $expr   = fn (string $col) => $factor === '* 100' ? "$col * 100" : ($sqlite ? "$col / 100" : "$col DIV 100");

        DB::update('UPDATE finance_months SET amount = '.$expr('amount'));
        DB::update('UPDATE finance_months SET income = '.$expr('income').' WHERE income IS NOT NULL');
        DB::update('UPDATE finance_month_items SET amount = '.$expr('amount'));
        DB::update('UPDATE finance_openings SET amount = '.$expr('amount'));

        DB::table('activity_log')->where('log_name', 'finance')->orderBy('id')->each(function ($row) use ($f) {
            $props = json_decode($row->properties ?? '', true);
            if (! is_array($props)) {
                return;
            }

            foreach (['before', 'after'] as $side) {
                if (! is_array($props[$side] ?? null)) {
                    continue;
                }
                foreach (['amount', 'income'] as $key) {
                    if (isset($props[$side][$key]) && is_numeric($props[$side][$key])) {
                        $props[$side][$key] = $f((int) $props[$side][$key]);
                    }
                }
                if (is_array($props[$side]['items'] ?? null)) {
                    $props[$side]['items'] = array_map(fn ($v) => is_numeric($v) ? $f((int) $v) : $v, $props[$side]['items']);
                }
            }

            DB::table('activity_log')->where('id', $row->id)->update(['properties' => json_encode($props, JSON_UNESCAPED_UNICODE)]);
        });
    }
};
