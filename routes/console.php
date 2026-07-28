<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تنظيف سجل المحاولات المرفوضة يومياً (يحذف الأقدم من مدة الاحتفاظ)
Schedule::command('feedback:prune-rejected')->dailyAt('03:30');
