<?php

namespace Database\Seeders;

use App\Models\CorrespondenceEntity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * أدوار المراسلات الثلاثة وموظفوها.
 *
 * ⚠️ **لا يُستدعى من DatabaseSeeder** ولا من أي هجرة — يُشغَّل يدوياً عند الطلب:
 *     php artisan db:seed --class=CorrespondenceRolesSeeder
 *
 * الأدوار بيانات لا كود، والـgit لا ينقلها؛ فوجود التعريف هنا يجعله مراجَعاً
 * وقابلاً للتكرار على أي بيئة، ولا يخالف قاعدة «الهجرات تزرع صلاحيات لا أدواراً».
 *
 * idempotent: syncPermissions يصحّح دوراً قائماً، وكلمة سر المستخدم القائم لا تُمسّ.
 */
class CorrespondenceRolesSeeder extends Seeder
{
    /** الدور => صلاحياته (بلا البادئة correspondence.) */
    private const ROLES = [
        'سكرتارية' => ['index', 'view', 'create', 'attachments', 'export'],
        'رئيس جهة' => ['index', 'view', 'create', 'attachments', 'export', 'assign', 'approve', 'stamp', 'delegate'],
        'مفتش'     => ['index', 'view', 'create', 'attachments'],
    ];

    /**
     * ⚠️ «حذف مكاتبة» غير ممنوحة لأحد: حذف من سجل رسمي مرقَّم فعل ثقيل،
     *    ومنح الصلاحية لاحقاً أهون من استرجاع رقم حُذف.
     * ⚠️ «مشاركة مع غير مستلم» غير ممنوحة: مؤجَّلة بقرار العميل (س٨) فلا كود ينفّذها،
     *    ومنحها يوهم بقدرة غير موجودة.
     * ⚠️ «إدارة أطراف المراسلات» تبقى لمدير النظام وحده.
     */
    private const USERS = [
        ['name' => 'أ. سمير',        'username' => 'samir',  'role' => 'سكرتارية', 'entity' => 'رئاسة المصلحة', 'job_title' => 'سكرتير أول'],
        ['name' => 'أ. خالد',        'username' => 'khaled', 'role' => 'رئيس جهة', 'entity' => 'رئاسة المصلحة', 'job_title' => 'رئيس القطاع'],
        ['name' => 'أ. محمد',        'username' => 'mohamed', 'role' => 'سكرتارية', 'entity' => 'المكتب الفني',  'job_title' => 'سكرتير'],
        ['name' => 'المستشار ياسر', 'username' => 'yasser', 'role' => 'رئيس جهة', 'entity' => 'المكتب الفني',  'job_title' => 'رئيس المكتب'],
        ['name' => 'أ. هبة',         'username' => 'heba',   'role' => 'مفتش',     'entity' => 'المكتب الفني',  'job_title' => 'مفتش فني'],
    ];

    /** أسماء مستخدمين كانت موجودة قبل التشغيل — تُخطَر في النهاية. */
    private array $adopted = [];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedRoles();
        $this->seedUsers();
    }

    private function seedRoles(): void
    {
        foreach (self::ROLES as $roleName => $abilities) {
            $permissions = array_map(fn ($a) => 'correspondence.'.$a, $abilities);

            $missing = array_diff($permissions, Permission::whereIn('name', $permissions)->pluck('name')->all());
            if ($missing) {
                throw new \RuntimeException('صلاحيات غير مزروعة: '.implode(', ', $missing).' — شغّل php artisan migrate أولاً.');
            }

            $role = Role::findOrCreate($roleName, 'web');

            // المستوى ١ دائماً: المستوى معناه «نشاط مَن أرى» ويُضبط من السلّم الإداري
            // (مفتش/مستشار/رئيس) لا من أدوار الفروع.
            $role->forceFill(['level' => 1])->save();

            $role->syncPermissions($permissions);

            $this->command->info("الدور «{$roleName}»: ".count($permissions).' صلاحية');
        }
    }

    private function seedUsers(): void
    {
        $rows = [];

        foreach (self::USERS as $spec) {
            $entity = CorrespondenceEntity::where('name', $spec['entity'])->first();
            if (! $entity) {
                throw new \RuntimeException("الطرف «{$spec['entity']}» غير موجود — شغّل php artisan migrate أولاً.");
            }

            $user     = User::where('username', $spec['username'])->first();
            $password = null;
            $adopted  = (bool) $user;

            if (! $user) {
                $password = Str::random(10);
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

            // دور واحد لكل مستخدم — sync يصحّح أي دور سابق
            $user->syncRoles([$spec['role']]);

            // ⚠️ المحافظات تُصفَّر للحساب الذي أنشأناه فقط. حسابٌ قائم قد يكون لموظف
            //    حقيقي له محافظات، وتصفيرها بلا إخطار خسارةٌ صامتة — يُخطَر بها أدناه.
            if (! $adopted) {
                $user->governorates()->sync([]);
            }

            if ($adopted) {
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

        $this->command->newLine();
        $this->command->table(
            ['الاسم', 'اسم المستخدم', 'الدور', 'الطرف', 'المسمّى', 'كلمة السر'],
            $rows
        );
        $this->command->warn('كلمات السر تُعرَض مرة واحدة ولا تُخزَّن في أي ملف — انسخها الآن، وليغيّرها كل مستخدم عند أول دخول.');

        if ($this->adopted) {
            $this->command->newLine();
            $this->command->error('⚠️ حسابات كانت موجودة وتبنّاها هذا السكربت: '.implode(', ', $this->adopted));
            $this->command->warn('اسمها ودورها وطرفها ومسمّاها أصبحت كما في الجدول أعلاه — **ودورها السابق استُبدل**.');
            $this->command->warn('محافظاتها لم تُمسّ. راجع كل حساب منها إن كان لموظف حقيقي يعمل على فرع آخر.');
        }
    }
}
