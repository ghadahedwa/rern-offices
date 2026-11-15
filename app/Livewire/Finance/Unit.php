<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceAccess;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * صفحة وحدة حسابية — تبويبٌ لكل حسابٍ تُدخله الوحدات (`FinanceAccount::UNIT_ACCOUNTS`)، والتبويب في
 * الرابط (`finance/units/{وحدة}/{حساب}`)، وأبنية المحاكم إن لم يُذكر.
 *
 * ⚠️ الحارس **الصلاحية والربط معاً** (`FinanceAccess::canViewUnit`): مستخدم القاهرة لا يفتح
 *    طنطا ولو كتب رابطها. والوحدة والحساب مقفلان في الحالة (#[Locked]) فلا يُبدَّلان من المتصفح.
 */
#[Layout('layouts.app')]
class Unit extends Component
{
    use Concerns\IsAccountPage;

    #[Locked]
    public string $tabKey = FinanceAccount::COURT_BUILDINGS;

    public function mount(FinanceUnit $financeUnit, string $account = 'court-buildings'): void
    {
        abort_unless(FinanceAccess::canViewUnit(auth()->user(), $financeUnit), 403);

        $model = FinanceAccount::bySlug($account);
        abort_unless($model->byUnits(), 404);

        $this->unitId = $financeUnit->id;
        $this->tabKey = $model->key;
        $this->initYear();
    }

    protected function accountKey(): string
    {
        return $this->tabKey;
    }
}
