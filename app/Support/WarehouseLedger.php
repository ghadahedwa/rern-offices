<?php

namespace App\Support;

use App\Exceptions\WarehouseException;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseIssue;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * خدمة مركزية لكل حركات المخزون — تحدّث الأرصدة وتكتب سجل الحركات (Ledger) في مكان واحد.
 * لا يُعدَّل رصيد أي صنف إلا من خلال هذه الخدمة (يضمن اتساق الرصيد مع السجل).
 */
class WarehouseLedger
{
    /**
     * رصيد افتتاحي: يضبط رصيد الصنف في المخزن إلى القيمة المُدخَلة.
     */
    public static function recordOpening(Warehouse $warehouse, Item $item, int $quantity, $user = null): WarehouseMovement
    {
        $userId = static::userId($user);

        return DB::transaction(function () use ($warehouse, $item, $quantity, $userId) {
            $stock  = static::lockStock($warehouse->id, $item->id);
            $before = $stock->quantity;
            $after  = $quantity;                 // الافتتاحي يضبط الرصيد (لا يضيف)
            $stock->update(['quantity' => $after]);

            return static::logMovement($warehouse->id, $item->id, 'opening', $quantity, $before, $after, null, $userId);
        });
    }

    /**
     * تسجيل الوارد: يضيف كميات كل بنود المستند إلى المخزن الرئيسي.
     */
    public static function recordIncoming(WarehouseIncoming $incoming): void
    {
        DB::transaction(function () use ($incoming) {
            foreach ($incoming->items as $line) {
                $stock  = static::lockStock($incoming->warehouse_id, $line->item_id);
                $before = $stock->quantity;
                $after  = $before + $line->quantity;
                $stock->update(['quantity' => $after]);

                static::logMovement(
                    $incoming->warehouse_id, $line->item_id, 'incoming',
                    $line->quantity, $before, $after, $incoming, $incoming->created_by
                );
            }
        });
    }

    /**
     * حذف بإرجاع: يتراجع عن أثر الوارد على الرصيد ثم يحذف المستند وحركاته.
     * (لا يوجد تعديل بعد الحفظ — الحذف هو الطريقة الوحيدة للتراجع.)
     *
     * @throws WarehouseException لو الرصيد الحالي أقل من كمية أحد البنود (حركات لاحقة استهلكته).
     */
    public static function reverseIncoming(WarehouseIncoming $incoming): void
    {
        DB::transaction(function () use ($incoming) {
            foreach ($incoming->items as $line) {
                $stock = static::lockStock($incoming->warehouse_id, $line->item_id);

                if ($stock->quantity < $line->quantity) {
                    $itemName = $line->item?->name ?? ('#'.$line->item_id);
                    throw new WarehouseException(
                        "لا يمكن حذف الوارد: الرصيد الحالي للصنف «{$itemName}» ({$stock->quantity}) "
                        ."أقل من الكمية الواردة ({$line->quantity})."
                    );
                }

                $stock->update(['quantity' => $stock->quantity - $line->quantity]);
            }

            WarehouseMovement::where('reference_type', $incoming->getMorphClass())
                ->where('reference_id', $incoming->id)
                ->delete();

            $incoming->delete();
        });
    }

    /**
     * تنفيذ النقل: خصم من المصدر وإضافة للمستلم لكل بند — إمّا كله أو لا شيء.
     *
     * @throws WarehouseException قاعدة النقل مخالفة أو رصيد أحد الأصناف غير كافٍ.
     */
    public static function recordTransfer(WarehouseTransfer $transfer): void
    {
        $from = $transfer->fromWarehouse()->with('type')->first();
        $to   = $transfer->toWarehouse()->with('type')->first();

        static::assertTransferAllowed($from, $to);

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $line) {
                $fromStock = static::lockStock($transfer->from_warehouse_id, $line->item_id);

                if ($fromStock->quantity < $line->quantity) {
                    $itemName = $line->item?->name ?? ('#'.$line->item_id);
                    throw new WarehouseException(
                        "الرصيد غير كافٍ للصنف «{$itemName}» في المخزن المصدر "
                        ."(المتاح {$fromStock->quantity}، المطلوب {$line->quantity})."
                    );
                }

                $toStock = static::lockStock($transfer->to_warehouse_id, $line->item_id);

                // خصم من المصدر
                $fb = $fromStock->quantity;
                $fa = $fb - $line->quantity;
                $fromStock->update(['quantity' => $fa]);
                static::logMovement(
                    $transfer->from_warehouse_id, $line->item_id, 'transfer_out',
                    $line->quantity, $fb, $fa, $transfer, $transfer->created_by
                );

                // إضافة للمستلم
                $tb = $toStock->quantity;
                $ta = $tb + $line->quantity;
                $toStock->update(['quantity' => $ta]);
                static::logMovement(
                    $transfer->to_warehouse_id, $line->item_id, 'transfer_in',
                    $line->quantity, $tb, $ta, $transfer, $transfer->created_by
                );
            }
        });
    }

    /**
     * حذف بإرجاع: يتراجع عن أثر النقل (يرجّع للمصدر، يخصم من المستلم) ثم يحذف المستند وحركاته.
     * (لا يوجد تعديل بعد الحفظ — الحذف هو الطريقة الوحيدة للتراجع.)
     *
     * @throws WarehouseException لو رصيد المستلم الحالي أقل من كمية أحد البنود (حركات لاحقة استهلكته).
     */
    public static function reverseTransfer(WarehouseTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $line) {
                $toStock = static::lockStock($transfer->to_warehouse_id, $line->item_id);

                if ($toStock->quantity < $line->quantity) {
                    $itemName = $line->item?->name ?? ('#'.$line->item_id);
                    throw new WarehouseException(
                        "لا يمكن حذف النقل: الرصيد الحالي للصنف «{$itemName}» في المخزن المستلم "
                        ."({$toStock->quantity}) أقل من الكمية المنقولة ({$line->quantity})."
                    );
                }

                $fromStock = static::lockStock($transfer->from_warehouse_id, $line->item_id);

                $toStock->update(['quantity' => $toStock->quantity - $line->quantity]);
                $fromStock->update(['quantity' => $fromStock->quantity + $line->quantity]);
            }

            WarehouseMovement::where('reference_type', $transfer->getMorphClass())
                ->where('reference_id', $transfer->id)
                ->delete();

            $transfer->delete();
        });
    }

    /**
     * الصرف إلى مقر: خصمٌ من المخزن بلا مستلِمٍ مخزنيّ — إمّا كله أو لا شيء.
     *
     * ⚠️ وهو **النوع الوحيد الذي يُنقص مخزناً بلا أن يزيد آخر**. وبه ينقص
     *    مخزن المحافظة، إذ لا ينقل هو لغيره — فرصيدُه قبل هذه الحركة كان
     *    مجموع ما وصله منذ نشأته لا ما فيه.
     *
     * ⚠️ ولا قاعدة مستوى هنا (بخلاف النقل): المقر ليس مخزناً وليس له مستوى،
     *    وأي مخزن يصرف إلى مقر — الرئيسي والإقليمي والفرعي سواء.
     *
     * @throws WarehouseException رصيد أحد الأصناف غير كافٍ.
     */
    public static function recordIssue(WarehouseIssue $issue): void
    {
        DB::transaction(function () use ($issue) {
            foreach ($issue->items as $line) {
                $stock = static::lockStock($issue->warehouse_id, $line->item_id);

                if ($stock->quantity < $line->quantity) {
                    $itemName = $line->item?->name ?? ('#'.$line->item_id);
                    throw new WarehouseException(
                        "الرصيد غير كافٍ للصنف «{$itemName}» في المخزن "
                        ."(المتاح {$stock->quantity}، المطلوب {$line->quantity})."
                    );
                }

                $before = $stock->quantity;
                $after  = $before - $line->quantity;
                $stock->update(['quantity' => $after]);

                static::logMovement(
                    $issue->warehouse_id, $line->item_id, 'issue',
                    $line->quantity, $before, $after, $issue, $issue->created_by
                );
            }
        });
    }

    /**
     * حذف بإرجاع: يردّ المصروف إلى المخزن ثم يحذف المستند وحركاته.
     * (لا تعديل بعد الحفظ — الحذف هو الطريقة الوحيدة للتراجع، كالوارد والنقل.)
     *
     * ⚠️ ولا يُفحص رصيدٌ هنا: الإرجاع **يزيد** المخزن، فلا يصطدم بنقص. وهذا
     *    بخلاف حذف الوارد والنقل حيث الإرجاع يخصم من طرفٍ قد تكون حركاتٌ
     *    لاحقة استهلكته.
     */
    public static function reverseIssue(WarehouseIssue $issue): void
    {
        DB::transaction(function () use ($issue) {
            foreach ($issue->items as $line) {
                $stock = static::lockStock($issue->warehouse_id, $line->item_id);
                $stock->update(['quantity' => $stock->quantity + $line->quantity]);
            }

            WarehouseMovement::where('reference_type', $issue->getMorphClass())
                ->where('reference_id', $issue->id)
                ->delete();

            $issue->delete();
        });
    }

    /**
     * قاعدة النقل: مسموح من مستوى أقل أو مساوٍ فقط — يُمنَع الصعود لأعلى.
     *
     * @throws WarehouseException
     */
    public static function assertTransferAllowed(Warehouse $from, Warehouse $to): void
    {
        if ($from->id === $to->id) {
            throw new WarehouseException('لا يمكن النقل من المخزن إلى نفسه.');
        }

        $fromLevel = $from->level();
        $toLevel   = $to->level();

        if ($fromLevel === null || $toLevel === null) {
            throw new WarehouseException('لا يمكن تحديد نوع أحد المخزنين لتطبيق قاعدة النقل.');
        }

        // مسموح: النزول لأدنى أو نفس المستوى. ممنوع: الصعود لأعلى.
        if ($fromLevel > $toLevel) {
            throw new WarehouseException('النقل إلى مستوى أعلى غير مسموح — الحركة تكون من الأعلى للأدنى أو بين نفس المستوى.');
        }
    }

    /** الحصول على صف الرصيد (وإنشاؤه بصفر لو غير موجود) مع قفل للتحديث. */
    protected static function lockStock(int $warehouseId, int $itemId): WarehouseStock
    {
        return WarehouseStock::query()
            ->lockForUpdate()
            ->firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $itemId],
                ['quantity' => 0]
            );
    }

    /** كتابة صف في سجل الحركات. */
    protected static function logMovement(
        int $warehouseId, int $itemId, string $type, int $quantity,
        int $before, int $after, ?Model $reference, $userId
    ): WarehouseMovement {
        return WarehouseMovement::create([
            'warehouse_id'   => $warehouseId,
            'item_id'        => $itemId,
            'type'           => $type,
            'quantity'       => $quantity,
            'balance_before' => $before,
            'balance_after'  => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id'   => $reference?->getKey(),
            'user_id'        => $userId,
            'created_at'     => now(),
        ]);
    }

    /** تطبيع المستخدم إلى معرّف. */
    protected static function userId($user): ?int
    {
        if ($user === null) {
            return null;
        }

        return is_object($user) ? $user->id : (int) $user;
    }
}
