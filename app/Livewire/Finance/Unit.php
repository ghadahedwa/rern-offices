<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * صفحة وحدة حسابية — شهورها بعناصرها الثمانية، بشكل صفحة أبنية المحاكم التي سبقتها (المرحلة د).
 *
 * ⚠️ الحارس **الصلاحية والربط معاً** (`FinanceAccess::canViewUnit`): مستخدم القاهرة لا يفتح
 *    طنطا ولو كتب رابطها. والوحدة مقفلةٌ في الحالة (`unitId` #[Locked]) فلا تُبدَّل من المتصفح.
 */
#[Layout('layouts.app')]
class Unit extends Component
{
    use Concerns\IsAccountPage;

    public function mount(FinanceUnit $financeUnit): void
    {
        abort_unless(FinanceAccess::canViewUnit(auth()->user(), $financeUnit), 403);

        $this->unitId = $financeUnit->id;
        $this->initYear();
    }

    protected function accountKey(): string
    {
        return FinanceAccount::COURT_BUILDINGS;
    }
}
