<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'correspondence_entity_id', 'job_title', 'all_warehouses'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'إضافة مستخدم',
                'updated' => 'تعديل مستخدم',
                'deleted' => 'حذف مستخدم',
                default   => $eventName,
            });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'all_warehouses' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function governorates(): BelongsToMany
    {
        return $this->belongsToMany(Governorate::class);
    }

    /**
     * نطاق المخازن — نظير «المحافظات» للمقرات: نطاق على المستخدم لا على الدور.
     *
     * ⚠️ لا تقرأ هذه العلاقة مباشرةً في استعلامات الفرع، بل عبر
     *    `App\Support\WarehouseScope`: القائمة الفارغة مع `all_warehouses = false`
     *    تعني **«لا يرى شيئاً»** لا «يرى الكل»، والخلط بينهما يفتح المنظومة كلها.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class);
    }

    /** الطرف الذي ينتمي إليه في المراسلات — نظير «المحافظات» للمقرات: نطاق على المستخدم لا على الدور. */
    public function correspondenceEntity(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceEntity::class);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
