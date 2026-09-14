<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * إجابة سؤال متعدد في تقييم المنصات الرقمية — صف لكل (رأي × سؤال × اختيار).
 * المفتاح مركّب في الجدول، ولا تحديث لصفوفه: تُنشأ مع الرأي وتُحذف معه.
 */
class FeedbackDigitalChoice extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['feedback_digital_rating_id', 'question', 'option'];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(FeedbackDigitalRating::class, 'feedback_digital_rating_id');
    }
}
