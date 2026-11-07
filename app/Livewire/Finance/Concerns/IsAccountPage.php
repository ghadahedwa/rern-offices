<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;

/**
 * صفحة حساب مالي — الصفحات الأربع تشترك فيها، وكلٌّ يحدّد حسابه بـ`accountKey()`.
 *
 * المرحلة (أ): الصفحة قائمة **بحراسة `finance.view` الحقيقية** وبمسارها الصحيح،
 * ليكون للفرع صفحة دخول وتُرى القائمة في مكانها. جدول الشهور يأتي في المرحلة (ب).
 */
trait IsAccountPage
{
    abstract protected function accountKey(): string;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('finance.view'), 403);
    }

    public function render()
    {
        $account = FinanceAccount::byKey($this->accountKey());

        return view('livewire.finance.account', [
            'account' => $account,
            'items'   => $account->kind === FinanceAccount::KIND_ITEMIZED
                ? CourtBuildingItem::active()->ordered()->get()
                : collect(),
        ])->title($account->label());
    }
}
