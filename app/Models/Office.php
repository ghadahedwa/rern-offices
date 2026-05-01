<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = [
        'governorate_id', 'parent_office_id',
        'name', 'established_at', 'type',
        'location_description', 'work_system', 'address',
        'google_maps_link', 'floors_description', 'connection_type',
        'avg_daily_transactions', 'contractual_status', 'structural_condition',
        'cleanliness_rating', 'working_hours', 'archive_rating',
        'microfilm', 'disabilities_access', 'fire_safety',
        'negatives_and_solutions', 'development_proposals',
    ];

    protected $casts = [
        'established_at' => 'date',
    ];

    public const TYPES = [
        'main'                 => 'مكتب رئيسي',
        'documentation_branch' => 'فرع توثيق',
        'registration_agency'  => 'مأمورية شهر',
        'merged'               => 'فرع توثيق ومأمورية شهر (مدمج)',
        'real_estate_register' => 'سجل عيني',
        'administration'       => 'إدارة',
    ];

    public const LOCATION_DESCRIPTIONS = [
        'regular'         => 'مقر عادي',
        'court'           => 'محكمة',
        'post'            => 'بريد',
        'urban_community' => 'مجتمع عمراني',
        'club'            => 'أندية',
        'investment'      => 'استثمار',
        'mall'            => 'مول',
        'orange'          => 'أورنج',
        'telecom'         => 'اتصالات',
    ];

    public const WORK_SYSTEMS = [
        'advance_booking' => 'حجز مسبق',
        'regular'         => 'عادي',
    ];

    public const CONNECTION_TYPES = [
        'fiber'          => 'فايبر',
        'copper'         => 'نحاسي',
        'wifi'           => 'واي فاي',
        'safety_network' => 'شبكة سلامة',
    ];

    public const CONTRACTUAL_STATUSES = [
        'new_rent'  => 'إيجار جديد',
        'old_rent'  => 'إيجار قديم',
        'owned'     => 'ملك',
        'allocated' => 'تخصيص',
    ];

    public const STRUCTURAL_CONDITIONS = [
        'good'                 => 'جيدة',
        'average'              => 'متوسطة',
        'deteriorated_upgrade' => 'متهالك ويحتاج رفع كفاءة',
        'deteriorated_replace' => 'متهالك ويحتاج لمقر بديل',
    ];

    public const CLEANLINESS_RATINGS = [
        'good'    => 'جيدة',
        'average' => 'متوسطة',
        'bad'     => 'سيئة',
    ];

    public const WORKING_HOURS = [
        'morning_eve1'      => 'صباحي ومسائي أول',
        'morning_eve1_eve2' => 'صباحي ومسائي أول وثاني',
        'evening_only'      => 'مسائي فقط',
    ];

    public const ARCHIVE_RATINGS = [
        'excellent' => 'ممتازة',
        'good'      => 'جيدة',
        'average'   => 'متوسطة',
        'bad'       => 'سيئة',
    ];

    public const MICROFILM_OPTIONS = [
        'al_ahram'  => 'يوجد ويتبع الأهرام',
        'al_akhbar' => 'يوجد ويتبع الأخبار',
        'none'      => 'لا يوجد',
    ];

    public const DISABILITIES_ACCESS = [
        'equipped'     => 'يوجد',
        'feasible'     => 'لا يوجد ويصلح لعمل التجهيزات',
        'not_feasible' => 'لا يوجد ولا يصلح',
    ];

    public const FIRE_SAFETY = [
        'auto'                      => 'إطفاء ذاتي',
        'extinguishers_ok'          => 'يوجد طفايات ولا تحتاج صيانة',
        'extinguishers_maintenance' => 'يوجد طفايات وتحتاج صيانة',
        'none'                      => 'لا يوجد طفايات حريق',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function parentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_office_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_office_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(OfficeMedia::class);
    }
}
