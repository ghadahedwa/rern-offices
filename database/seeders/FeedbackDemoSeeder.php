<?php

namespace Database\Seeders;

use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\SuggestionTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * بيانات تجريبية لشاشات نتائج رأي المواطن — للتطوير المحلي فقط.
 *
 *   php artisan db:seed --class=FeedbackDemoSeeder
 *
 * ⚠️ يرفض العمل على production، ولا يُستدعى من DatabaseSeeder.
 * ⚠️ يمسح بيانات البوابة الموجودة قبل الزرع حتى تبقى العيّنة نظيفة ومتوقَّعة.
 */
class FeedbackDemoSeeder extends Seeder
{
    /** أسماء عيّنة — بيانات وهمية بحتة */
    private const NAMES = [
        'أحمد محمود سيد', 'منى عبد الرحمن', 'خالد إبراهيم علي', 'سارة حسن فؤاد',
        'محمد عبد الله شعبان', 'هدى مصطفى كامل', 'ياسر السيد رشاد', 'نورهان طارق',
        'عمرو صلاح الدين', 'ريهام أنور', 'مصطفى جمال', 'دينا سمير',
    ];

    private const NOTES = [
        'الموظفون متعاونون لكن الانتظار طويل في ساعات الذروة.',
        'المكان يحتاج مقاعد إضافية لكبار السن.',
        'تم إنهاء المعاملة بسرعة، شكراً للقائمين على العمل.',
        'التكييف غير كافٍ والمكان مزدحم.',
        'أرجو توضيح المستندات المطلوبة على لافتة قبل الدخول.',
        'دورات المياه تحتاج نظافة أفضل.',
    ];

    private const FREE_SUGGESTIONS = [
        'إتاحة الدفع الإلكتروني للرسوم.',
        'فتح شباك إضافي في أول الشهر لأن الزحام يزيد.',
        'إرسال رسالة نصية عند جاهزية المستند.',
        'توفير موقف للسيارات بجوار المقر.',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('FeedbackDemoSeeder ممنوع على production — لم يُنفَّذ شيء.');

            return;
        }

        $offices = Office::publicFeedback()->get(['id', 'governorate_id', 'name']);

        if ($offices->isEmpty()) {
            $this->command?->warn('لا توجد مقرات من أنواع ظاهرة للمواطن — فعّل is_public على نوع مقر واحد على الأقل ثم أعد المحاولة.');

            return;
        }

        $topicIds = SuggestionTopic::pluck('id');
        if ($topicIds->isEmpty()) {
            $this->command?->warn('كتالوج المقترحات فارغ — شغّل SuggestionCatalogSeeder أولاً.');

            return;
        }

        $this->wipe();

        $offices = $offices->shuffle()->take(15);
        $ratings = 0;
        $suggestions = 0;

        foreach ($offices as $index => $office) {
            // مقر أو اثنان بعينة صغيرة عمداً — لاختبار مجموعة "عينة غير كافية"
            $count = $index < 2 ? random_int(1, 3) : random_int(5, 18);

            // انحياز المقر: بعض المقرات جيدة وبعضها ضعيفة حتى يظهر الترتيب
            $bias = random_int(0, 2);   // 0 = ضعيف، 1 = متوسط، 2 = جيد

            for ($i = 0; $i < $count; $i++) {
                $this->makeRating($office, $bias);
                $ratings++;
            }

            foreach (range(1, random_int(2, 8)) as $ignored) {
                $this->makeSuggestion($office, $topicIds);
                $suggestions++;
            }
        }

        $this->makeRejectedAttempts($offices);

        $this->command?->info("تم زرع {$ratings} تقييم و{$suggestions} مقترح على {$offices->count()} مقر.");
    }

    private function wipe(): void
    {
        DB::table('feedback_suggestion_topic')->delete();
        FeedbackSuggestion::query()->delete();
        FeedbackRating::query()->delete();
        FeedbackRejectedAttempt::query()->delete();
    }

    private function makeRating(Office $office, int $bias): void
    {
        $star = fn () => min(5, max(1, random_int(1, 3) + $bias));
        $createdAt = now()->subDays(random_int(0, 90))->subMinutes(random_int(0, 1440));

        FeedbackRating::create([
            'governorate_id'       => $office->governorate_id,
            'office_id'            => $office->id,
            'name'                 => $this->pick(self::NAMES),
            'national_id'          => $this->nationalId(),
            'phone'                => $this->phone(),
            'wait_time'            => $this->pick(array_keys(FeedbackRating::WAIT_TIMES)),
            'rating_speed'         => $star(),
            'rating_staff'         => $star(),
            'rating_queue'         => $star(),
            'rating_cleanliness'   => $star(),
            'rating_clarity'       => $star(),
            // اختياري: نتركه فارغاً في ~40% من الصفوف لاختبار حساب المتوسط على المجيبين فقط
            'rating_accessibility' => random_int(1, 10) <= 6 ? $star() : null,
            'overall_rating'       => $star(),
            'notes'                => random_int(1, 10) <= 4 ? $this->pick(self::NOTES) : null,
            'ip_address'           => '127.0.0.1',
            'user_agent'           => 'FeedbackDemoSeeder',
        ])->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
    }

    private function makeSuggestion(Office $office, $topicIds): void
    {
        $createdAt = now()->subDays(random_int(0, 90))->subMinutes(random_int(0, 1440));

        $suggestion = FeedbackSuggestion::create([
            'governorate_id'   => $office->governorate_id,
            'office_id'        => $office->id,
            'name'             => $this->pick(self::NAMES),
            'national_id'      => $this->nationalId(),
            'phone'            => $this->phone(),
            'other_suggestion' => random_int(1, 10) <= 3 ? $this->pick(self::FREE_SUGGESTIONS) : null,
            'ip_address'       => '127.0.0.1',
            'user_agent'       => 'FeedbackDemoSeeder',
        ]);

        $suggestion->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        $suggestion->topics()->sync($topicIds->shuffle()->take(random_int(1, 4)));
    }

    private function makeRejectedAttempts($offices): void
    {
        $reasons = ['duplicate_window', 'rate_limit', 'honeypot'];

        foreach (range(1, 25) as $ignored) {
            $office = $offices->random();
            $createdAt = now()->subDays(random_int(0, 29));

            FeedbackRejectedAttempt::create([
                'type'        => $this->pick(['rating', 'suggestion']),
                'national_id' => $this->nationalId(),
                'phone'       => $this->phone(),
                'office_id'   => $office->id,
                'reason'      => $this->pick($reasons),
                'ip_address'  => '127.0.0.'.random_int(2, 250),
                'user_agent'  => 'FeedbackDemoSeeder',
            ])->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }

    /** رقم قومي وهمي بصيغة صحيحة (٢ + تاريخ ميلاد + كود محافظة + تسلسل) */
    private function nationalId(): string
    {
        return '2'
            .str_pad((string) random_int(70, 99), 2, '0', STR_PAD_LEFT)
            .str_pad((string) random_int(1, 12), 2, '0', STR_PAD_LEFT)
            .str_pad((string) random_int(1, 28), 2, '0', STR_PAD_LEFT)
            .'01'
            .str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function phone(): string
    {
        return '01'.$this->pick(['0', '1', '2', '5']).str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
}
