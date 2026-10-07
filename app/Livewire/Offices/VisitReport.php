<?php

namespace App\Livewire\Offices;

use App\Models\Office;
use App\Models\StructuralCondition;
use App\Support\OfficeVisitReports;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * صفحة تقرير زيارة واحد لمقر واحد (المفتش أو المستشار) — `/offices/{office}/visit-reports/{type}`.
 *
 * صاحب صلاحية التعديل يكتب ويحفظ، وصاحب العرض وحده يقرأ الخانات نفسها مقفولة.
 * الخصائص بأسماء البنود المجرّدة (`visited_at`…) وتُحفظ في أعمدة التقرير بالبادئة.
 */
#[Layout('layouts.app')]
#[Title('تقرير زيارة')]
class VisitReport extends Component
{
    #[Locked]
    public int $officeId;

    #[Locked]
    public string $type;

    public string $visited_at = '';
    public $structural_condition_id = null;
    public string $cleanliness_rating = '';
    public string $archive_rating = '';
    public string $work_schedule_commitment = '';
    public string $citizen_treatment_commitment = '';
    public string $office_needs = '';
    public string $negatives_and_solutions = '';
    public string $development_proposals = '';

    public function mount(Office $office, string $type): void
    {
        abort_unless(OfficeVisitReports::isType($type), 404);

        $user = auth()->user();
        abort_unless(OfficeVisitReports::canView($user, $type), 403);
        abort_unless(OfficeVisitReports::inScope($user, $office), 403);

        $this->officeId = $office->id;
        $this->type     = $type;

        foreach (OfficeVisitReports::FIELDS as $field) {
            $value = $office->{OfficeVisitReports::column($type, $field)};
            $this->{$field} = match ($field) {
                'visited_at'              => $value?->format('Y-m-d') ?? '',
                'structural_condition_id' => $value,
                default                   => $value ?? '',
            };
        }
    }

    public function canEdit(): bool
    {
        return OfficeVisitReports::canEdit(auth()->user(), $this->type);
    }

    /**
     * ⚠️ الحراسة هنا لا في القالب: الخانات المقفولة لا تمنع نداءً يصل من المتصفح،
     *    والصلاحية أو محافظات المستخدم قد تتغيّر والصفحة مفتوحة.
     */
    public function save(): void
    {
        $office = Office::findOrFail($this->officeId);
        abort_unless($this->canEdit(), 403);
        abort_unless(OfficeVisitReports::inScope(auth()->user(), $office), 403);

        $commitment = implode(',', array_keys(Office::COMMITMENT_RATINGS));

        // البنود كلها اختيارية — القواعد تحرس القيمة الواصلة من العميل لا وجودها
        $this->validate([
            'visited_at'                   => 'nullable|date',
            'structural_condition_id'      => 'nullable|exists:structural_conditions,id',
            'cleanliness_rating'           => 'nullable|in:' . implode(',', array_keys(Office::CLEANLINESS_RATINGS)),
            'archive_rating'               => 'nullable|in:' . implode(',', array_keys(Office::ARCHIVE_RATINGS)),
            'work_schedule_commitment'     => 'nullable|in:' . $commitment,
            'citizen_treatment_commitment' => 'nullable|in:' . $commitment,
            'office_needs'                 => 'nullable|string|max:65000',
            'negatives_and_solutions'      => 'nullable|string|max:65000',
            'development_proposals'        => 'nullable|string|max:65000',
        ]);

        $data = [];
        foreach (OfficeVisitReports::FIELDS as $field) {
            $data[OfficeVisitReports::column($this->type, $field)] = $this->{$field} ?: null;
        }
        $office->update($data);

        Flux::toast(variant: 'success', text: __('home.vr_saved'));
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.offices.visit-reports.show', [
            'office'               => Office::with(['governorate:id,name', 'officeType:id,name'])->findOrFail($this->officeId),
            'title'                => __(OfficeVisitReports::REPORTS[$this->type]['label']),
            'canEditReport'        => $this->canEdit(),
            'canViewOffice'        => $user?->hasRole('super-admin') || $user?->can('offices.view') || $user?->can('offices.edit'),
            'structuralConditions' => StructuralCondition::orderBy('id')->get(),
        ]);
    }
}
