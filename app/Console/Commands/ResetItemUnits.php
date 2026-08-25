<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يمسح وحدات الأصناف ويعيد عدّاد المعرّفات إلى ١.
 *
 * ⚠️ يُشغَّل **قبل** زرع الأصناف لا بعده: مفتاح `items.item_unit_id` أجنبيٌّ
 *    بـnullOnDelete، فحذف الوحدات بعد الزرع لا يُخطئ — بل يُفرّغ وحدة كل صنف
 *    في صمت. ولهذا يرفض الأمرُ العملَ ما دام صنفٌ واحد مرتبطاً بوحدة.
 */
class ResetItemUnits extends Command
{
    protected $signature = 'warehouses:reset-item-units {--force : التنفيذ رغم وجود أصناف مرتبطة بوحدات}';

    protected $description = 'مسح وحدات الأصناف وإعادة ترقيمها من ١ (يُشغَّل قبل زرع الأصناف)';

    public function handle(): int
    {
        if (! Schema::hasTable('item_units')) {
            $this->error('جدول item_units غير موجود — شغّل الهجرات أولاً.');

            return self::FAILURE;
        }

        $linked = Item::whereNotNull('item_unit_id')->count();

        if ($linked > 0 && ! $this->option('force')) {
            $this->error("مرفوض: {$linked} صنفاً مرتبطٌ بوحدة.");
            $this->line('حذف الوحدات الآن يُفرّغ وحدة كل صنف منها في صمت (nullOnDelete).');
            $this->line('شغّل الأمر قبل زرع الأصناف، أو أعد زرعها بعده، أو مرّر --force عن قصد.');

            return self::FAILURE;
        }

        $count = ItemUnit::count();
        ItemUnit::query()->delete();
        $this->resetAutoIncrement();

        $this->info("حُذفت {$count} وحدة، وأُعيد العدّاد إلى ١.");

        if ($linked > 0) {
            $this->warn("تنبيه: {$linked} صنفاً فقد وحدته الآن — أعد تشغيل زرع الأصناف.");
        }

        $this->line('التالي:  php artisan db:seed --class=WarehouseItemsFromStatementsSeeder');

        return self::SUCCESS;
    }

    /**
     * إعادة العدّاد آمنةٌ هنا وحدها لأن الجدول صار فارغاً؛ ولو بقيت فيه صفوف
     * لأعطى معرّفاً مستعملاً لصفٍّ جديد فارتبط به ما كان يشير إلى غيره.
     * والتعبير يختلف بين المحرّكين، ولا يوجد ما يُعيده في محرّكات أخرى.
     */
    private function resetAutoIncrement(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE item_units AUTO_INCREMENT = 1'),
            'sqlite' => DB::statement("DELETE FROM sqlite_sequence WHERE name = 'item_units'"),
            default  => $this->warn('لم يُعَد العدّاد: المحرّك غير مدعوم في هذا الأمر.'),
        };
    }
}
