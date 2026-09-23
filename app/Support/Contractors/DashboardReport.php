<?php

namespace App\Support\Contractors;

use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\Profession;
use App\Support\ContractorScope;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * أرقام لوحة فرع العاملين بالتعاقد — **مصدرٌ واحد تقرأ منه الشاشة**.
 *
 * ⚠️ **أرقام الحضور من `AttendanceReportQuery` نفسه الذي تقرأ منه التقارير** — لوحةٌ
 *    تحسب حضورها بنفسها تخالف تقريرَ الفترة نفسها، والمستخدم يرى رقمين لشيءٍ واحد.
 *    فهنا **التوزيع والأعداد** وحدها، والحضور يُفوَّض.
 * ⚠️ **النطاق في كل استعلام**: `ContractorScope` للعاملين، ومحافظاتُ المستخدم للتوزيع —
 *    بدونه تعرض اللوحة أعداد الجمهورية لمفتشٍ له محافظة.
 */
final class DashboardReport
{
    public function __construct(
        private readonly AttendanceReportQuery $query,
        private readonly ?Authenticatable $user = null,
    ) {}

    /** المحافظات داخل نطاق المستخدم — أساس كل توزيع في اللوحة. */
    private function scopedGovernorateIds(): ?array
    {
        $scope    = ContractorScope::governorateIds($this->user);
        $selected = $this->query->governorateIds;

        if ($scope !== null) {
            $selected = $selected === [] ? $scope : array_values(array_intersect($selected, $scope));

            return $selected;   // قد تكون [] — فلا يرى شيئاً، وهو المقصود
        }

        return $selected === [] ? null : $selected;
    }

    /**
     * أعداد رأسية: العاملون على رأس العمل · المقارّ التي بها عاملون · المحافظات ·
     * المؤرشَفون.
     *
     * ⚠️ **«على رأس العمل» = `Contractor::inService()`** (تسكينٌ مفتوح) — نفس تعريف
     *    شاشة العاملين حرفياً. تعريفٌ ثانٍ هنا يجعل اللوحة تخالف القائمة برقمٍ أو اثنين
     *    بلا أن يعرف أحدٌ أيُّهما الصحيح.
     */
    public function headline(): array
    {
        $ids = $this->scopedGovernorateIds();

        if ($ids === []) {
            return ['in_service' => 0, 'offices' => 0, 'governorates' => 0, 'archived' => 0];
        }

        $inService = $this->scopedContractors($ids)->inService()->count();
        $archived  = $this->scopedContractors($ids)
            ->whereDoesntHave('assignments', fn (Builder $q) => $q->whereNull('ended_on'))
            ->count();

        // المقارّ والمحافظات من التسكينات المفتوحة نفسها — بـjoin لا بعلاقةٍ على المقر
        // (لا علاقة `contractorAssignments` على `Office`، ولا داعي لإضافتها لعدّتين).
        $open = fn () => ContractorAssignment::query()
            ->join('offices', 'offices.id', '=', 'contractor_assignments.office_id')
            ->whereNull('contractor_assignments.ended_on')
            ->when($ids !== null, fn ($q) => $q->whereIn('offices.governorate_id', $ids));

        $offices      = $open()->distinct()->count('contractor_assignments.office_id');
        $governorates = $open()->distinct()->count('offices.governorate_id');

        return [
            'in_service'   => $inService,
            'offices'      => $offices,
            'governorates' => $governorates,
            'archived'     => $archived,
        ];
    }

    /**
     * توزيع العاملين على المحافظات — **كل محافظات النطاق حتى الخالية**.
     *
     * ⚠️ المحافظة بصفرٍ معلومة: «لا عاملين في بني سويف» ليس «بني سويف غير موجودة».
     *    وحذفُ الصفر من المخطط يُخفي الثغرة التي يبحث عنها المفتش.
     *
     * @return array<int, array{label:string, value:int}>
     */
    public function byGovernorate(): array
    {
        $ids = $this->scopedGovernorateIds();

        if ($ids === []) {
            return [];
        }

        $counts = ContractorAssignment::query()
            ->selectRaw('offices.governorate_id as gov_id, COUNT(DISTINCT contractor_assignments.contractor_id) as total')
            ->join('offices', 'offices.id', '=', 'contractor_assignments.office_id')
            ->whereNull('contractor_assignments.ended_on')
            ->when($ids !== null, fn ($q) => $q->whereIn('offices.governorate_id', $ids))
            ->groupBy('offices.governorate_id')
            ->pluck('total', 'gov_id');

        return Governorate::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('order')->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Governorate $gov) => [
                'label' => $gov->name,
                'value' => (int) ($counts[$gov->id] ?? 0),
            ])
            ->all();
    }

    /**
     * توزيع العاملين على الصفات (مدخل بيانات · مترجم · …).
     *
     * @return array<int, array{label:string, value:int}>
     */
    public function byProfession(): array
    {
        $ids = $this->scopedGovernorateIds();

        if ($ids === []) {
            return [];
        }

        $counts = $this->scopedContractors($ids)
            ->inService()
            ->selectRaw('profession_id, COUNT(*) as total')
            ->groupBy('profession_id')
            ->pluck('total', 'profession_id');

        return Profession::query()
            ->orderBy('order')->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Profession $profession) => [
                'label' => $profession->name,
                'value' => (int) ($counts[$profession->id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)   // صفةٌ لا أحد عليها ليست ثغرة
            ->values()
            ->all();
    }

    /** أرقام الحضور في الفترة — **مفوَّضة للمحرّك نفسه الذي تقرأ منه التقارير**. */
    public function attendance(): array
    {
        $rows = $this->query->rows();

        return AttendanceReport::sum($rows) + [
            'unrecorded' => AttendanceReport::unrecordedContractors($rows),
            'statuses'   => AttendanceReport::statusColumns($rows),
        ];
    }

    public function breakdown(): array
    {
        return $this->query->report()->breakdown();
    }

    /** @return Builder<Contractor> */
    private function scopedContractors(?array $ids): Builder
    {
        $query = Contractor::query();

        ContractorScope::applyToContractors($query, $this->user);

        if ($ids !== null) {
            $query->whereHas(
                'assignments',
                fn (Builder $q) => $q->whereHas('office', fn (Builder $o) => $o->whereIn('governorate_id', $ids))
            );
        }

        return $query;
    }
}
