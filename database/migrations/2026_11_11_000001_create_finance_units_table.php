<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الوحدات الحسابية — أبنية المحاكم مجموع ما تُدخله (ملف العميل «الشئون المالية.docx» 2026-11-11).
 *
 * قائمة مرجعية تحت «إدارة النظام» للسوبر أدمن (نمط عناصر أبنية المحاكم)، مزروعةٌ بالست.
 * ربط المستخدم بالوحدة ورقمُ الشهر على الوحدة يأتيان في خطواتٍ تالية.
 */
return new class extends Migration
{
    private const UNITS = [
        'الوحدة الحسابية بالقاهرة',
        'الوحدة الحسابية بالإسكندرية',
        'الوحدة الحسابية بطنطا',
        'الوحدة الحسابية بالأقصر',
        'الوحدة الحسابية بأسيوط',
        'الوحدة الحسابية بالإسماعيلية',
    ];

    public function up(): void
    {
        Schema::create('finance_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach (self::UNITS as $i => $name) {
            DB::table('finance_units')->insert([
                'name' => $name, 'order' => $i + 1, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_units');
    }
};
