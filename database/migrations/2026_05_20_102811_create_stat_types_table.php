<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stat_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period', ['yearly', 'monthly']);
            $table->string('group_key'); // transactions_sales | claims | requests
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();
        });

        // الأنواع الـ 3 الموجودة أولاً (IDs ثابتة لضمان تطابق التحويل)
        DB::table('stat_types')->insert([
            ['id' => 1, 'name' => 'معاملات المكتب',          'period' => 'yearly',  'group_key' => 'transactions_sales', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'بيع النماذج',              'period' => 'monthly', 'group_key' => 'transactions_sales', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'بيع الحوافظ',              'period' => 'monthly', 'group_key' => 'transactions_sales', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'المحصل من الطلبات',        'period' => 'monthly', 'group_key' => 'claims',             'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'عدد طلبات الشهر',          'period' => 'yearly',  'group_key' => 'requests',           'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'عدد طلبات السجل العيني',   'period' => 'yearly',  'group_key' => 'requests',           'order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stat_types');
    }
};
