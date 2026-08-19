<?php

namespace Database\Seeders;

use App\Models\CorrespondenceEntity;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * الحسابات الخمسة للعمل على المراسلات محلياً.
 *
 * ⚠️ **يرفض production رفضاً تاماً** — بقرار المستخدمة (2026-08-19): الحسابات تجريبية
 *    بكلمة سر موحّدة، ولا يجوز أن تصل الإنتاج بأي حال، ولا بضبط متغيّر بيئة.
 *    وحسابات الإنتاج الحقيقية تُنشأ من شاشة المستخدمين بأسمائها وكلمات سرّها.
 *
 * ⚠️ لا يُستدعى من DatabaseSeeder ولا من أي هجرة — يُشغَّل يدوياً:
 *     php artisan db:seed --class=CorrespondenceDemoUsersSeeder
 *
 * الأدوار في CorrespondenceRolesSeeder، ويُشغَّل من هنا تلقائياً.
 */
class CorrespondenceDemoUsersSeeder extends Seeder
{
    private const PASSWORD = '1234';

    private const USERS = [
        ['name' => 'أ. سمير',        'username' => 'samir',   'role' => 'سكرتارية', 'entity' => 'رئاسة المصلحة', 'job_title' => 'سكرتير أول'],
        ['name' => 'أ. خالد',        'username' => 'khaled',  'role' => 'رئيس جهة', 'entity' => 'رئاسة المصلحة', 'job_title' => 'رئيس القطاع'],
        ['name' => 'أ. محمد',        'username' => 'mohamed', 'role' => 'سكرتارية', 'entity' => 'المكتب الفني',  'job_title' => 'سكرتير'],
        ['name' => 'المستشار ياسر', 'username' => 'yasser',  'role' => 'رئيس جهة', 'entity' => 'المكتب الفني',  'job_title' => 'رئيس المكتب'],
        ['name' => 'أ. هبة',         'username' => 'heba',    'role' => 'مفتش',     'entity' => 'المكتب الفني',  'job_title' => 'مفتش فني'],
    ];

    /** أسماء مستخدمين كانت موجودة قبل التشغيل — تُخطَر في النهاية. */
    private array $adopted = [];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('CorrespondenceDemoUsersSeeder ممنوع على production — لم يُنفَّذ شيء.');
            $this->command?->warn('حسابات الإنتاج تُنشأ من شاشة المستخدمين. والأدوار وحدها: db:seed --class=CorrespondenceRolesSeeder');

            return;
        }

        $this->call(CorrespondenceRolesSeeder::class);

        $rows = [];

        foreach (self::USERS as $spec) {
            $entity = CorrespondenceEntity::where('name', $spec['entity'])->first();
            if (! $entity) {
                throw new \RuntimeException("الطرف «{$spec['entity']}» غير موجود — شغّل php artisan migrate أولاً.");
            }

            $user     = User::where('username', $spec['username'])->first();
            $adopted  = (bool) $user;
            $password = null;

            if (! $user) {
                $password = self::PASSWORD;
                $user     = User::create([
                    'name'     => $spec['name'],
                    'username' => $spec['username'],
                    'password' => $password,
                ]);
            }

            $user->update([
                'name'                     => $spec['name'],
                'correspondence_entity_id' => $entity->id,
                'job_title'                => $spec['job_title'],
            ]);

            $user->syncRoles([$spec['role']]);

            // ⚠️ المحافظات تُصفَّر للحساب الذي أنشأناه فقط. حسابٌ قائم قد يكون لموظف
            //    يعمل على فرع آخر، وتصفيرها بلا إخطار خسارةٌ صامتة — يُخطَر بها أدناه.
            if (! $adopted) {
                $user->governorates()->sync([]);
            } else {
                $this->adopted[] = $spec['username'];
            }

            $rows[] = [
                $spec['name'],
                $spec['username'],
                $spec['role'],
                $spec['entity'],
                $spec['job_title'],
                $password ?? 'لم تُمسّ (حساب قائم)',
            ];
        }

        $this->report($rows);
    }

    private function report(array $rows): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->table(
            ['الاسم', 'اسم المستخدم', 'الدور', 'الطرف', 'المسمّى', 'كلمة السر'],
            $rows
        );

        if ($this->adopted) {
            $this->command->newLine();
            $this->command->error('⚠️ حسابات كانت موجودة وتبنّاها هذا السكربت: '.implode(', ', $this->adopted));
            $this->command->warn('اسمها ودورها وطرفها ومسمّاها أصبحت كما في الجدول — **ودورها السابق استُبدل**. محافظاتها لم تُمسّ.');
        }
    }
}
