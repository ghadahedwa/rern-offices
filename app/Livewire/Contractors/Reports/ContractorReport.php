<?php

namespace App\Livewire\Contractors\Reports;

use App\Exports\ContractorsAttendanceExport;
use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Support\Contractors\AttendanceReport;
use App\Support\ContractorScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تقرير العامل — صفٌّ لكل مدة تسكين في المدى، **ومعه تواريخ غيابه وإجازاته**.
 *
 * تفصيل التواريخ هو ما يميّز هذا التقرير عن تقرير المقر: «غاب ٢» رقمٌ يُراجَع،
 * و«غاب يوم ٢ ويوم ٧» واقعةٌ يُسأل عنها.
 */
#[Layout('layouts.app')]
#[Title('تقرير العامل')]
class ContractorReport extends Component
{
    use Concerns\BuildsAttendanceReport;

    public ?int $governorateId = null;

    public ?int $contractorId = null;

    public function updatedGovernorateId(): void
    {
        $this->contractorId = null;
    }

    protected function appliedFilters(): array
    {
        return [
            'governorateId' => $this->governorateId ? (int) $this->governorateId : null,
            'contractorId'  => $this->contractorId ? (int) $this->contractorId : null,
        ];
    }

    protected function filterKeys(): array
    {
        return ['governorateId', 'contractorId'];
    }

    /**
     * العامل المطلوب — **يُقرأ عبر النطاق لا بـ`findOrFail`**، فمعرّفٌ مدسوس من
     * محافظةٍ أخرى لا يُخرج بياناته.
     */
    protected function subject(): ?Contractor
    {
        $id = $this->applied['contractorId'] ?? null;

        if ($id === null) {
            return null;
        }

        return ContractorScope::applyToContractors(
            Contractor::query()->whereKey($id)->with('profession:id,name')
        )->first();
    }

    protected function buildRows(): array
    {
        $report     = $this->report();
        $contractor = $this->subject();

        if (! $report || ! $contractor) {
            return [];
        }

        // ⚠️ حتى لتقرير عاملٍ بعينه تُقصَر المقارّ على النطاق: تسكينه في محافظةٍ
        //    ليست لي لا يظهر في تقريري وإن كان هو مرئياً لي.
        $officeIds = $this->scopedOfficeIds(array_filter([$this->applied['governorateId'] ?? null]));

        if ($officeIds === []) {
            return [];
        }

        return $report->rows(collect([$contractor]), $officeIds);
    }

    /**
     * تواريخ الاستثناءات داخل المدى — **المعروضة منها ما دخل الحساب وحده**.
     *
     * ⚠️ يومٌ سُجِّل ثم صار عطلةً لا يُعدّ في الأرقام، فعرضه هنا يُظهر تناقضاً
     *    بين التفصيل والعمود. لذلك يُقرأ التفصيل من أيام الصفوف لا من الجدول مباشرة.
     *
     * @return array<string, array{date:string, status:string, color:string}>
     */
    public function exceptionDates(array $rows): array
    {
        $contractor = $this->subject();
        $report     = $this->report();

        if (! $contractor || ! $report || $rows === []) {
            return [];
        }

        $counted = array_sum(array_map(fn ($row) => array_sum($row['exceptions']), $rows));

        if ($counted === 0) {
            return [];
        }

        $officeIds = array_column($rows, 'office_id');
        $statuses  = AttendanceStatus::query()->get(['id', 'name', 'color'])->keyBy('id');
        $calendar  = array_flip($report->calendar());

        // أيام هذا العامل التي يملكها أحد الصفوف المعروضة — حدود العرض هي حدود الحساب.
        $spans = array_map(fn ($row) => [$row['started_on'], $row['ended_on'], $row['office_name']], $rows);

        $out = [];

        AttendanceDay::query()
            ->where('attendable_type', Contractor::class)
            ->where('attendable_id', $contractor->getKey())
            ->between($report->start, $report->end)
            ->orderBy('date')
            ->get()
            ->each(function (AttendanceDay $day) use (&$out, $statuses, $calendar, $spans) {
                $key = $day->date->toDateString();

                if (! isset($calendar[$key])) {
                    return; // جمعة أو عطلة — خارج الحساب فخارج التفصيل
                }

                foreach ($spans as [$from, $to, $office]) {
                    if ($key >= $from && ($to === null || $key <= $to)) {
                        $out[$key] = [
                            'date'   => $key,
                            'status' => $statuses[$day->status_id]->name ?? '—',
                            'color'  => $statuses[$day->status_id]->color ?? '#a1a1aa',
                            'office' => $office,
                        ];

                        return;
                    }
                }
            });

        ksort($out);

        return $out;
    }

    public function exportExcel()
    {
        if (! $this->guardExport()) {
            return;
        }

        $rows = $this->buildRows();

        return Excel::download(
            new ContractorsAttendanceExport(
                rows: $rows,
                statuses: AttendanceReport::statusColumns($rows),
                subjectLabel: __('home.ct_rep_office_col'),
                subjectKey: 'office_name',
                withContractorCount: false,
                title: __('home.ct_rep_contractor_title').' — '.($this->subject()?->name ?? ''),
                period: $this->periodLabel(),
                breakdown: $this->report()?->breakdown() ?? []
            ),
            $this->exportFileName('contractor-attendance')
        );
    }

    public function render()
    {
        $rows   = $this->hasSearched ? $this->buildRows() : [];
        $report = $this->report();

        return view('livewire.contractors.reports.contractor', [
            'governorates' => ContractorScope::governorateOptions(),
            'candidates'   => $this->contractorOptions(),
            'subject'      => $this->hasSearched ? $this->subject() : null,
            'rows'         => $rows,
            'statuses'     => AttendanceReport::statusColumns($rows),
            'totals'       => AttendanceReport::sum($rows),
            'exceptions'   => $this->exceptionDates($rows),
            'breakdown'    => $this->hasSearched && $report ? $report->breakdown() : null,
            'holidays'     => $this->hasSearched && $report ? $report->holidays() : [],
        ]);
    }

    /** منسدلة العاملين — مقصورة على النطاق وعلى المحافظة المختارة. */
    protected function contractorOptions()
    {
        $query = Contractor::query();

        ContractorScope::applyToContractors($query);

        if ($this->governorateId) {
            $query->whereHas(
                'assignments',
                fn ($q) => $q->whereHas('office', fn ($o) => $o->where('governorate_id', $this->governorateId))
            );
        }

        return $query->orderBy('name')->orderBy('id')->get(['id', 'name', 'phone']);
    }
}
