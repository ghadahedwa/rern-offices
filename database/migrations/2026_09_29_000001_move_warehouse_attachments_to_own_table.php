<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مرفقات مستندات المخازن: من عمودين على كل مستند (ملف واحد) إلى جدولٍ
 * مشترك (حتى خمسة ملفات للمستند، وتُضاف بعد الحفظ).
 *
 * ⚠️ النوع مفتاحٌ قصير (`incoming`…) لا اسم الكلاس — اسم الكلاس في عمودٍ
 *    يجعل أي نقلٍ للموديل هجرةَ بيانات.
 * ⚠️ الأعمدة القديمة تُحذف بعد النقل لا تُترك: عمودٌ باقٍ يبدو حيّاً وقد بطل
 *    تحديثه (المرفق المضاف لاحقاً لا يصله).
 */
return new class extends Migration
{
    /** نوع المستند ← جدوله */
    private const TABLES = [
        'incoming' => 'warehouse_incomings',
        'transfer' => 'warehouse_transfers',
        'issue'    => 'warehouse_issues',
    ];

    public function up(): void
    {
        Schema::create('warehouse_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20);
            $table->unsignedBigInteger('document_id');
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
        });

        foreach (self::TABLES as $type => $table) {
            $expected = DB::table($table)->whereNotNull('attachment_path')->count();

            DB::table($table)->whereNotNull('attachment_path')->orderBy('id')
                ->chunk(500, function ($rows) use ($type) {
                    DB::table('warehouse_attachments')->insert($rows->map(fn ($r) => [
                        'document_type' => $type,
                        'document_id'   => $r->id,
                        'path'          => $r->attachment_path,
                        'original_name' => $r->attachment_original_name,
                        'uploaded_by'   => $r->created_by,
                        'created_at'    => $r->created_at,
                        'updated_at'    => $r->created_at,
                    ])->all());
                });

            $moved = DB::table('warehouse_attachments')->where('document_type', $type)->count();

            // ⚠️ الأعمدة تُحذف بعد هذا السطر — فلا تُحذف إلا والعدد مطابق
            if ($moved !== $expected) {
                throw new RuntimeException("warehouse_attachments: {$table} expected {$expected}, moved {$moved}");
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['attachment_path', 'attachment_original_name']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $type => $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('attachment_path')->nullable();
                $t->string('attachment_original_name')->nullable();
            });

            // العمودان يحملان ملفاً واحداً — فيرجع أول مرفق، والباقي يضيع مع الجدول
            $first = DB::table('warehouse_attachments')
                ->where('document_type', $type)
                ->selectRaw('MIN(id) as id')
                ->groupBy('document_id');

            DB::table('warehouse_attachments')->whereIn('id', $first)->orderBy('id')
                ->each(function ($a) use ($table) {
                    DB::table($table)->where('id', $a->document_id)->update([
                        'attachment_path'          => $a->path,
                        'attachment_original_name' => $a->original_name,
                    ]);
                });
        }

        Schema::dropIfExists('warehouse_attachments');
    }
};
