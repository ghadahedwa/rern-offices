<?php

namespace App\Reports;

use App\Models\Governorate;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * تقرير العمل أيام الجمع — المصدر الواحد للأرقام: الشاشة والـPDF والـExcel يقرؤون منه،
 * فلا يخرج ملفٌّ بأرقامٍ تخالف الشاشة.
 *
 * ⚠️ «لم يُحدَّد» (NULL) فئةٌ ثالثة لا «لا تعمل»: الحقل أُضيف 2026-10-14 فكل السيارات
 *    القائمة وقتها بلا قيمة، واحتسابها «لا تعمل» كان سيُخرج تقريراً كاذباً حتى تُستكمل.
 */
class VehicleFridayWork
{
    /** مفاتيح الفئات بترتيب العرض — `works`/`not_works` تقابلان 1/0 في العمود */
    public const KEYS = ['works', 'not_works', 'unset'];

    private function __construct(
        private ?array $allowedGovIds,
        private array $govIds,
        private string $friday,
        private string $sort = '',
        private string $dir = 'asc',
    ) {}

    /**
     * أعمدة قائمة السيارات القابلة للترتيب: مفتاح الرابط ← عمود SQL (قائمة بيضاء — `TableSort`).
     * المحافظة بترتيبها المعتمد لا أبجدياً، والجداول المرتبطة استعلامات فرعية.
     *
     * @return array<string, mixed>
     */
    public static function sortMap(): array
    {
        return [
            'governorate' => \App\Support\TableSort::related('governorates', 'order', 'vehicles', 'governorate_id'),
            'name'        => 'vehicles.name',
            'type'        => \App\Support\TableSort::related('vehicle_types', 'name', 'vehicles', 'type_id'),
            'friday'      => 'vehicles.works_on_friday',
        ];
    }

    /**
     * يبني التقرير من فلاترٍ قد تصل من العميل أو من الـsession — كل قيمة تُفحص:
     * معرّف المحافظة رقمٌ خالص، والفئة من القائمة البيضاء، والتالف يُهمَل.
     *
     * @param  ?array  $allowedGovIds  null = بلا حدّ (super-admin) · [] = لا يرى شيئاً
     */
    public static function make(?array $allowedGovIds, array $filters): self
    {
        $govIds = collect($filters['governorateIds'] ?? [])
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->values()->all();

        $friday = $filters['friday'] ?? '';
        $friday = is_string($friday) && in_array($friday, self::KEYS, true) ? $friday : '';

        // الترتيب يُفحص هنا أيضاً — يصل من الـsession للـPDF، و`TableSort` يُسقط المجهول للافتراضي
        $sort = is_string($filters['sort'] ?? null) ? $filters['sort'] : '';
        $dir  = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return new self($allowedGovIds, $govIds, $friday, $sort, $dir);
    }

    public static function keyOf(?bool $value): string
    {
        return match ($value) {
            true    => 'works',
            false   => 'not_works',
            default => 'unset',
        };
    }

    /** @return array<string,string> مفتاح الفئة => تسميتها */
    public static function labels(): array
    {
        return [
            'works'     => Vehicle::FRIDAY_WORK[1],
            'not_works' => Vehicle::FRIDAY_WORK[0],
            'unset'     => __('home.friday_work_unset'),
        ];
    }

    public function friday(): string
    {
        return $this->friday;
    }

    public function governorates(): Collection
    {
        return Governorate::query()
            ->when($this->allowedGovIds !== null, fn ($q) => $q->whereIn('id', $this->allowedGovIds))
            ->when($this->govIds, fn ($q) => $q->whereIn('id', $this->govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * الملخص: counts[gov_id][key] = عدد. لا يتأثر بفلتر الفئة — الملخص نفسه هو التقسيم بها.
     *
     * @return array<int, array<string,int>>
     */
    public function counts(): array
    {
        $rows = $this->scoped()
            ->selectRaw('governorate_id, works_on_friday, COUNT(*) as cnt')
            ->groupBy('governorate_id', 'works_on_friday')
            ->get();

        $counts = [];
        foreach ($rows as $r) {
            $key = self::keyOf($r->works_on_friday === null ? null : (bool) $r->works_on_friday);
            $counts[$r->governorate_id][$key] = ($counts[$r->governorate_id][$key] ?? 0) + (int) $r->cnt;
        }

        return $counts;
    }

    /** قائمة السيارات — مفلترة بالفئة المختارة، بترتيب المستخدم أو الافتراضي (المحافظات ثم الاسم) */
    public function vehicles(): Collection
    {
        $query = $this->scoped()
            ->with(['governorate:id,name', 'type:id,name'])
            ->when($this->friday === 'works', fn ($q) => $q->where('works_on_friday', true))
            ->when($this->friday === 'not_works', fn ($q) => $q->where('works_on_friday', false))
            ->when($this->friday === 'unset', fn ($q) => $q->whereNull('works_on_friday'));

        return \App\Support\TableSort::apply(
            $query, $this->sort, $this->dir, self::sortMap(),
            fn ($q) => $q
                ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
                ->orderBy('governorate_id')
                ->orderBy('name'),
            'vehicles.id', 'asc',
        )->get();
    }

    private function scoped()
    {
        return Vehicle::query()
            ->when($this->allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $this->allowedGovIds))
            ->when($this->govIds, fn ($q) => $q->whereIn('governorate_id', $this->govIds));
    }
}
