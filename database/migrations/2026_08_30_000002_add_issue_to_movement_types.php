<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * توسيع `warehouse_movements.type` ليقبل النوع الخامس `issue`.
 *
 * ⚠️ العمود **enum بقيدٍ في قاعدة البيانات** لا نصٌّ حرّ — فإضافة نوعٍ في
 *    الكود وحده تُرفض عند أول حفظ:
 *    MySQL «Data truncated for column 'type'» · sqlite «CHECK constraint failed».
 *    ولم يُكتشف ذلك بقراءة الكود بل بأول اختبار حفظٍ فعلي.
 *
 * ⚠️ ولا يُستعمل `$table->enum(...)->change()`: تغييرُ enum يحتاج
 *    doctrine/dbal في MySQL ولا تدعمه sqlite أصلاً. فـMySQL بـSQL صريح،
 *    وsqlite **تُعاد بناؤها** (لا ALTER لقيد CHECK فيها) — والاختبارات
 *    تعمل عليها، فلا بد من الفرعين.
 */
return new class extends Migration
{
    private const TYPES = ['opening', 'incoming', 'transfer_out', 'transfer_in', 'issue'];

    public function up(): void
    {
        $this->rebuild(self::TYPES);
    }

    public function down(): void
    {
        // الرجوع يحذف حركات الصرف أولاً — وإلا رفض القيدُ الصفوفَ القائمة
        DB::table('warehouse_movements')->where('type', 'issue')->delete();

        $this->rebuild(['opening', 'incoming', 'transfer_out', 'transfer_in']);
    }

    /** @param  array<int, string>  $types */
    private function rebuild(array $types): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $list   = "'".implode("','", $types)."'";

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE warehouse_movements MODIFY COLUMN type ENUM({$list}) NOT NULL");

            return;
        }

        if ($driver === 'sqlite') {
            // sqlite لا تعدّل قيد CHECK — فيُبنى الجدول من جديد وتُنقل صفوفه.
            // (الفهارس تُعاد معه، وبلا ذلك يبقى الجدول القديم باسمٍ مؤقت.)
            $check = "CHECK (\"type\" in ({$list}))";

            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("CREATE TABLE warehouse_movements_new (
                id integer primary key autoincrement not null,
                warehouse_id integer not null,
                item_id integer not null,
                type varchar not null {$check},
                quantity integer not null,
                balance_before integer not null,
                balance_after integer not null,
                reference_type varchar null,
                reference_id integer null,
                user_id integer null,
                created_at datetime null,
                foreign key(warehouse_id) references warehouses(id) on delete cascade,
                foreign key(item_id) references items(id) on delete cascade,
                foreign key(user_id) references users(id) on delete set null
            )");

            DB::statement('INSERT INTO warehouse_movements_new SELECT id, warehouse_id, item_id, type,
                quantity, balance_before, balance_after, reference_type, reference_id, user_id, created_at
                FROM warehouse_movements');

            DB::statement('DROP TABLE warehouse_movements');
            DB::statement('ALTER TABLE warehouse_movements_new RENAME TO warehouse_movements');

            DB::statement('CREATE INDEX warehouse_movements_warehouse_id_item_id_index ON warehouse_movements (warehouse_id, item_id)');
            DB::statement('CREATE INDEX warehouse_movements_type_index ON warehouse_movements (type)');
            DB::statement('CREATE INDEX warehouse_movements_reference_type_reference_id_index ON warehouse_movements (reference_type, reference_id)');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // محرّك آخر: لا قيد enum يُعدَّل — يُترك كما هو
    }
};
