<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * مرفق مستند مخازن (وارد · نقل · صرف) — صورة أو PDF، حتى خمسة للمستند.
 */
class WarehouseAttachment extends Model
{
    /** أقصى عدد مرفقات للمستند الواحد — عند الإنشاء وبعده معاً */
    public const MAX_PER_DOCUMENT = 5;

    /** قواعد الملف الواحد: تحقّق Livewire والإضافة اللاحقة يقرآنها معاً */
    public const FILE_RULES = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];

    /** نوع المستند (كما يُخزَّن) ← موديله */
    public const DOCUMENTS = [
        'incoming' => WarehouseIncoming::class,
        'transfer' => WarehouseTransfer::class,
        'issue'    => WarehouseIssue::class,
    ];

    protected $fillable = [
        'document_type',
        'document_id',
        'path',
        'original_name',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/' . $this->path);
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower($this->path), '.pdf');
    }

    protected static function booted(): void
    {
        // الملف يُحذف بعد اعتماد المعاملة — حذف المستند يمرّ بمعاملةٍ تُرجع الرصيد،
        // ولو تراجعت بقي الصفّ وقد ضاع ملفّه
        static::deleted(function (self $attachment) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($attachment->path));
        });
    }
}
