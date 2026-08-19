<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // بادئة الرقم المعروض: «رئاسة ٢٠٢٦/١٤١». فريدة لأن دورها منع الخلط بصرياً،
            // وتكرارها يُبطل الغرض منها.
            $table->string('code', 8)->unique();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الطرفان المؤكَّدان من العميل. القائمة قابلة للتوسّع من الشاشة.
        DB::table('correspondence_entities')->insert([
            [
                'name'       => 'رئاسة المصلحة',
                'code'       => 'رئاسة',
                'order'      => 1,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'المكتب الفني',
                'code'       => 'فني',
                'order'      => 2,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_entities');
    }
};
