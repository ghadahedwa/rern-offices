<?php

use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceMonthItem;
use App\Models\FinanceOpening;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

/*
 * هجرة المبالغ إلى القروش (2026-11-25): القيم القائمة بالجنيه ×١٠٠ — الشهور والعناصر والأرصدة الافتتاحية
 * **ومبالغ سجلّ التصحيح**، وإلا عُرض التاريخ القديم مقسوماً على مئة.
 */

uses(RefreshDatabase::class);

it('تضرب المبالغ القائمة وسجلّها في ١٠٠، والعكس يعيدها', function () {
    $unit  = FinanceUnit::ordered()->first();
    $item  = CourtBuildingItem::ordered()->first();
    $month = FinanceMonth::create([
        'finance_account_id' => FinanceAccount::byKey('telecom')->id,
        'finance_unit_id'    => $unit->id,
        'month'              => '2026-10-01',
        'amount'             => 7000,
        'income'             => 10000,
    ]);
    $court = FinanceMonth::create([
        'finance_account_id' => FinanceAccount::byKey('court_buildings')->id,
        'finance_unit_id'    => $unit->id,
        'month'              => '2026-10-01',
        'amount'             => 1500,
    ]);
    FinanceMonthItem::create(['finance_month_id' => $court->id, 'court_building_item_id' => $item->id, 'amount' => 1500]);
    FinanceOpening::create(['finance_account_id' => FinanceAccount::byKey('telecom')->id, 'finance_unit_id' => $unit->id, 'amount' => 5000]);

    activity(FinanceHistory::LOG)->event(FinanceHistory::EVENT_CORRECTED)->withProperties([
        'account' => 'court_buildings', 'unit' => $unit->id, 'month' => '2026-10',
        'before'  => ['items' => ['قيمة' => 1000], 'amount' => 1000, 'income' => 300, 'orders' => [['number' => '12', 'images' => 1]]],
        'after'   => ['items' => ['قيمة' => 1500], 'amount' => 1500, 'orders' => []],
    ])->log('corrected');
    activity(FinanceHistory::LOG)->event(FinanceHistory::EVENT_NOTE_EDITED)->withProperties(['note_before' => 'أ', 'note_after' => 'ب'])->log('note');

    $migration = require database_path('migrations/2026_11_25_000001_finance_amounts_to_piasters.php');
    $migration->up();

    expect($month->fresh()->amount)->toBe(700000)
        ->and($month->fresh()->income)->toBe(1000000)
        ->and(FinanceMonthItem::sole()->amount)->toBe(150000)
        ->and(FinanceOpening::sole()->amount)->toBe(500000);

    $log = Activity::where('event', FinanceHistory::EVENT_CORRECTED)->sole();
    expect($log->properties['before']['amount'])->toBe(100000)
        ->and($log->properties['before']['income'])->toBe(30000)
        ->and($log->properties['before']['items'])->toBe(['قيمة' => 100000])
        ->and($log->properties['after']['amount'])->toBe(150000)
        // ما ليس مبلغاً لا يُمسّ
        ->and($log->properties['before']['orders'])->toBe([['number' => '12', 'images' => 1]])
        ->and(Activity::where('event', FinanceHistory::EVENT_NOTE_EDITED)->sole()->properties['note_after'])->toBe('ب');

    $migration->down();
    expect($month->fresh()->amount)->toBe(7000)
        ->and(Activity::where('event', FinanceHistory::EVENT_CORRECTED)->sole()->properties['after']['items'])->toBe(['قيمة' => 1500]);
});
