<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * سجلّ تصحيح الشهور وحذفها (قرار العميل): مَن · متى · لماذا · من كم إلى كم.
 *
 * ⚠️ **بلا subject**: الحساب والشهر في الخصائص. سجلّ نشاط داشبورد المقرات يعرض كل ما له
 *    subject ليس مستخدماً أو دوراً، فكان الشهر المالي سيظهر فيه — ونمط رأي المواطن نفسه.
 *    وتصحيحُ شهرٍ حُذف ثم أُعيد إدخاله يبقى ظاهراً عليه: الشهر واحد والسجلّ تاريخه.
 */
class FinanceHistory
{
    public const LOG = 'finance';

    public const EVENT_CORRECTED = 'corrected';
    public const EVENT_DELETED   = 'deleted';

    /** صورة الشهر كما تُحفظ في السجلّ — أرقامٌ لا نصوص، فالعرض يُترجمها وقتها */
    public static function snapshot(FinanceMonth $month): array
    {
        $month->loadMissing('paymentOrders.attachments', 'items.item');

        // أبنية المحاكم: قيمة كل عنصر **باسمه وقت الحفظ** — اسمٌ يُعدَّل لاحقاً لا يغيّر التاريخ
        $items = $month->items->mapWithKeys(fn ($i) => [$i->item->name => (int) $i->amount])->all();

        return ($items ? ['items' => $items] : []) + [
            'amount' => (int) $month->amount,
            'orders' => $month->paymentOrders->map(fn ($order) => [
                'number'    => $order->number,
                'issued_on' => $order->issued_on?->toDateString(),
                'images'    => $order->attachments->count(),
            ])->values()->all(),
        ];
    }

    public static function record(
        string $event,
        FinanceAccount $account,
        CarbonInterface $month,
        string $reason,
        array $before,
        ?array $after,
        int $imagesAdded = 0,
        int $imagesRemoved = 0,
    ): void {
        activity(self::LOG)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties([
                'account'        => $account->key,
                'month'          => $month->format('Y-m'),
                'reason'         => $reason,
                'before'         => $before,
                'after'          => $after,
                'images_added'   => $imagesAdded,
                'images_removed' => $imagesRemoved,
            ])
            ->log($event);
    }

    /** سجلّ الحساب مجمَّعاً بالشهر (`Y-m`) — الأحدث أولاً */
    public static function forAccount(FinanceAccount $account): Collection
    {
        return Activity::query()
            ->where('log_name', self::LOG)
            ->where('properties->account', $account->key)
            ->with('causer')
            ->latest('id')
            ->get()
            ->groupBy(fn (Activity $a) => $a->properties['month'] ?? '');
    }

    /**
     * ما تغيّر بلغة المستخدم: [['label', 'from', 'to'], …] — المبلغ وأوامر الدفع،
     * وصورٌ أُضيفت أو حُذفت ولو بقي عددها (استُبدلت صورة بأخرى).
     */
    public static function changes(Activity $activity): array
    {
        $before = $activity->properties['before'] ?? [];
        $after  = $activity->properties['after'] ?? null;
        $lines  = [];

        if ($after === null) {
            return [[
                'label' => __('home.fin_history_deleted_values'),
                'from'  => self::describe($before),
                'to'    => null,
            ]];
        }

        // العنصر الذي تغيّر بالتحديد — قبل الإجمالي (هو السبب والإجمالي نتيجته)
        foreach ($after['items'] ?? [] as $name => $value) {
            $old = $before['items'][$name] ?? null;
            if ($old !== $value) {
                $lines[] = [
                    'label' => $name,
                    'from'  => $old === null ? null : number_format((int) $old),
                    'to'    => number_format((int) $value),
                ];
            }
        }

        if (($before['amount'] ?? null) !== ($after['amount'] ?? null)) {
            $lines[] = [
                'label' => isset($after['items']) ? __('home.fin_total_label') : __('home.fin_amount_label'),
                'from'  => number_format((int) ($before['amount'] ?? 0)),
                'to'    => number_format((int) ($after['amount'] ?? 0)),
            ];
        }

        $from = self::describeOrders($before['orders'] ?? []);
        $to   = self::describeOrders($after['orders'] ?? []);
        if ($from !== $to) {
            $lines[] = ['label' => __('home.fin_payment_order'), 'from' => $from, 'to' => $to];
        }

        $added   = (int) ($activity->properties['images_added'] ?? 0);
        $removed = (int) ($activity->properties['images_removed'] ?? 0);
        if ($added || $removed) {
            $lines[] = [
                'label' => __('home.fin_history_images'),
                'from'  => null,
                'to'    => __('home.fin_history_images_changed', ['added' => $added, 'removed' => $removed]),
            ];
        }

        return $lines;
    }

    /**
     * يقسم سجلّ الشهر (الأحدث أولاً) عند آخر حذف: ما قبله يخصّ الصفّ المحفوظ الآن، وما
     * بعده (والحذف نفسه) يخصّ إدخالاً سابقاً زال. بالترتيب لا بالوقت — الأوقات قد تتساوى.
     *
     * @return array{0: Collection, 1: Collection}
     */
    public static function splitAtDeletion(Collection $events): array
    {
        $current = $events->takeUntil(fn (Activity $a) => $a->event === self::EVENT_DELETED);

        return [$current->values(), $events->slice($current->count())->values()];
    }

    /** المبلغ وأوامر الدفع في سطرٍ واحد — لسطر «الأصل» */
    public static function summary(array $snapshot): string
    {
        return self::describe($snapshot);
    }

    protected static function describe(array $snapshot): string
    {
        return number_format((int) ($snapshot['amount'] ?? 0)).' — '.self::describeOrders($snapshot['orders'] ?? []);
    }

    protected static function describeOrders(array $orders): string
    {
        if (! $orders) {
            return __('home.fin_no_order');
        }

        return collect($orders)->map(fn (array $o) => __('home.fin_history_order', [
            'number' => $o['number'],
            'date'   => $o['issued_on'],
            'count'  => $o['images'],
        ]))->implode(' · ');
    }
}
