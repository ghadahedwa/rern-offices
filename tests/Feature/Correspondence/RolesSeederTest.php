<?php

use App\Models\CorrespondenceEntity;
use App\Models\User;
use Database\Seeders\CorrespondenceRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CorrespondenceRolesSeeder::class);
});

it('ينشئ الأدوار الثلاثة بمستوى ١', function () {
    foreach (['سكرتارية', 'رئيس جهة', 'مفتش'] as $name) {
        $role = Role::where('name', $name)->first();

        expect($role)->not->toBeNull("الدور «{$name}» لم يُنشأ")
            ->and($role->level)->toBe(1);
    }
});

it('يمنح كل دور صلاحياته المتفق عليها بالضبط', function () {
    $expected = [
        'سكرتارية' => ['attachments', 'create', 'export', 'index', 'view'],
        'رئيس جهة' => ['approve', 'assign', 'attachments', 'create', 'delegate', 'export', 'index', 'stamp', 'view'],
        'مفتش'     => ['attachments', 'create', 'index', 'view'],
    ];

    foreach ($expected as $roleName => $abilities) {
        $actual = Role::where('name', $roleName)->first()
            ->permissions->pluck('name')
            ->map(fn ($n) => str_replace('correspondence.', '', $n))
            ->sort()->values()->all();

        expect($actual)->toBe($abilities, "صلاحيات «{$roleName}» لا تطابق المتفق عليه");
    }
});

it('لا يمنح الحذف ولا المشاركة ولا الإعدادات لأي دور', function () {
    // الحذف من سجل مرقَّم فعل ثقيل · المشاركة مؤجَّلة بلا كود ينفّذها · الإعدادات لمدير النظام
    foreach (['سكرتارية', 'رئيس جهة', 'مفتش'] as $roleName) {
        $names = Role::where('name', $roleName)->first()->permissions->pluck('name');

        expect($names)->not->toContain('correspondence.delete')
            ->and($names)->not->toContain('correspondence.share')
            ->and($names)->not->toContain('correspondence.settings');
    }
});

it('الاعتماد والختم لرئيس الجهة وحده', function () {
    foreach (['correspondence.approve', 'correspondence.stamp'] as $permission) {
        expect(Role::where('name', 'رئيس جهة')->first()->hasPermissionTo($permission))->toBeTrue()
            ->and(Role::where('name', 'سكرتارية')->first()->hasPermissionTo($permission))->toBeFalse()
            ->and(Role::where('name', 'مفتش')->first()->hasPermissionTo($permission))->toBeFalse();
    }
});

it('ينشئ المستخدمين الخمسة بطرفهم ومسمّاهم', function () {
    $expected = [
        'samir'   => ['أ. سمير', 'سكرتارية', 'رئاسة المصلحة', 'سكرتير أول'],
        'khaled'  => ['أ. خالد', 'رئيس جهة', 'رئاسة المصلحة', 'رئيس القطاع'],
        'mohamed' => ['أ. محمد', 'سكرتارية', 'المكتب الفني', 'سكرتير'],
        'yasser'  => ['المستشار ياسر', 'رئيس جهة', 'المكتب الفني', 'رئيس المكتب'],
        'heba'    => ['أ. هبة', 'مفتش', 'المكتب الفني', 'مفتش فني'],
    ];

    foreach ($expected as $username => [$name, $role, $entity, $jobTitle]) {
        $user = User::where('username', $username)->first();

        expect($user)->not->toBeNull("المستخدم «{$username}» لم يُنشأ")
            ->and($user->name)->toBe($name)
            ->and($user->roles->pluck('name')->all())->toBe([$role])
            ->and($user->correspondenceEntity->name)->toBe($entity)
            ->and($user->job_title)->toBe($jobTitle)
            ->and($user->governorates)->toHaveCount(0);
    }
});

it('خالد وياسر بنفس الدور وطرفين مختلفين ومسمّيين مختلفين', function () {
    $khaled = User::where('username', 'khaled')->first();
    $yasser = User::where('username', 'yasser')->first();

    expect($khaled->roles->first()->name)->toBe($yasser->roles->first()->name)
        ->and($khaled->correspondence_entity_id)->not->toBe($yasser->correspondence_entity_id)
        ->and($khaled->job_title)->not->toBe($yasser->job_title);
});

it('يعطي الحسابات الجديدة كلمة السر الموحّدة محلياً', function () {
    foreach (['samir', 'mohamed', 'yasser', 'heba'] as $username) {
        expect(Hash::check('1234', User::where('username', $username)->first()->password))
            ->toBeTrue("كلمة سر «{$username}» ليست الموحّدة");
    }
});

it('يرفض كلمة السر الافتراضية على الإنتاج', function () {
    // حساب بكلمة سر معروفة على الإنتاج أسوأ من فشل السكربت.
    // يُختبَر الحارس مباشرة: db:seed على الإنتاج يطلب تأكيداً فيتوقف قبل بلوغه.
    app()['env'] = 'production';

    expect(fn () => CorrespondenceRolesSeeder::resolvePassword())
        ->toThrow(RuntimeException::class, 'CORR_SEED_PASSWORD');
});

it('يقبل كلمة سر مضبوطة على الإنتاج', function () {
    app()['env'] = 'production';
    putenv('CORR_SEED_PASSWORD=a-real-one');

    expect(CorrespondenceRolesSeeder::resolvePassword())->toBe('a-real-one');

    putenv('CORR_SEED_PASSWORD');
});

it('لا يغيّر كلمة سر حساب قائم عند إعادة التشغيل', function () {
    $before = User::where('username', 'samir')->first()->password;

    $this->seed(CorrespondenceRolesSeeder::class);

    expect(User::where('username', 'samir')->first()->password)->toBe($before);
});

it('يصحّح دوراً عُدِّلت صلاحياته يدوياً', function () {
    $role = Role::where('name', 'مفتش')->first();
    $role->givePermissionTo('correspondence.approve');

    $this->seed(CorrespondenceRolesSeeder::class);

    expect($role->fresh()->hasPermissionTo('correspondence.approve'))->toBeFalse();
});

it('لا يصفّر محافظات حساب كان موجوداً قبل التشغيل', function () {
    // حساب قائم قد يكون لموظف حقيقي يعمل على فرع آخر — تصفير محافظاته خسارة صامتة
    $gov  = \App\Models\Governorate::create(['name' => 'المنيا', 'order' => 1]);
    $mine = User::factory()->create(['username' => 'tarek']);
    $mine->governorates()->sync([$gov->id]);

    // نضيف طرفاً بنفس اسم المستخدم إلى قائمة السكربت عبر إعادة تسمية حساب قائم
    User::where('username', 'heba')->first()->delete();
    $adopted = User::factory()->create(['username' => 'heba']);
    $adopted->governorates()->sync([$gov->id]);

    $this->seed(CorrespondenceRolesSeeder::class);

    expect($adopted->fresh()->governorates)->toHaveCount(1)
        ->and($mine->fresh()->governorates)->toHaveCount(1);
});

it('يصحّح طرفاً أو مسمّى عُدِّل يدوياً', function () {
    $user  = User::where('username', 'heba')->first();
    $other = CorrespondenceEntity::where('name', 'رئاسة المصلحة')->first();
    $user->update(['correspondence_entity_id' => $other->id, 'job_title' => 'خطأ']);

    $this->seed(CorrespondenceRolesSeeder::class);

    expect($user->fresh()->correspondenceEntity->name)->toBe('المكتب الفني')
        ->and($user->fresh()->job_title)->toBe('مفتش فني');
});
