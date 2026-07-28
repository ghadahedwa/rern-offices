<?php

namespace App\Console\Commands;

use App\Models\FeedbackRejectedAttempt;
use Illuminate\Console\Command;

class PruneFeedbackRejected extends Command
{
    protected $signature = 'feedback:prune-rejected';

    protected $description = 'حذف سجل المحاولات المرفوضة الأقدم من مدة الاحتفاظ (config: feedback.rejected_retention_days)';

    public function handle(): int
    {
        $days = (int) config('feedback.rejected_retention_days', 30);
        $deleted = FeedbackRejectedAttempt::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("تم حذف {$deleted} صفاً من المحاولات المرفوضة الأقدم من {$days} يوماً.");

        return self::SUCCESS;
    }
}
