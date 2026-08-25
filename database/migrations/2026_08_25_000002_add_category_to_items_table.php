<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // nullable في القاعدة و required في الفورم: الأصناف المسجَّلة قبل الأقسام تبقى
            // كما هي وتُصنَّف تدريجياً عبر فلتر «بلا قسم»، ولا يُقبل صنف جديد بلا قسم.
            $table->foreignId('item_category_id')->nullable()->after('name')
                ->constrained('item_categories')->nullOnDelete();

            // ترتيب الصنف داخل قسمه — البيان الورقي مرقَّم بترتيب ثابت يراجعه أمين المخزن
            // سطراً بسطر، والترتيب الأبجدي يزيحه مع كل صنف جديد.
            $table->integer('order')->default(0)->after('min_stock');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['item_category_id']);
            $table->dropColumn(['item_category_id', 'order']);
        });
    }
};
