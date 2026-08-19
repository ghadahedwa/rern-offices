<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * طرف في منظومة المراسلات — صاحب دفتر صادر ووارد.
 * الرقم يتولّد لكل (طرف + سنة) على حِدة، فالطرف الجديد يأخذ دفتره تلقائياً.
 */
class CorrespondenceEntity extends Model
{
    use HasFactory;

    protected $table = 'correspondence_entities';

    protected $fillable = ['name', 'code', 'order', 'is_active'];

    protected $casts = [
        'order'     => 'integer',
        'is_active' => 'boolean',
    ];

    /** موظفو الطرف — «المستخدمون تحت كل طرف» (طلب العميل 2026-08-19). */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'correspondence_entity_id');
    }
}
