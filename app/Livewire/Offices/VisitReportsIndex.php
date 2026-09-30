<?php

namespace App\Livewire\Offices;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Governorate;
use App\Models\Office;
use App\Support\ArabicText;
use App\Support\LocalTime;
use App\Support\OfficeVisitReports;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * قائمة مقرات تقرير زيارة (المفتش أو المستشار) — بابه من المنيو الجانبية
 * (`/visit-reports/{type}`). صاحب صلاحية التقرير وحدها يصل منها إلى مقراته
 * بلا حاجةٍ إلى قائمة المقرات ولا شاشة عرضها.
 */
#[Layout('layouts.app')]
#[Title('تقارير الزيارة')]
class VisitReportsIndex extends Component
{
    use WithPagination, WithPerPage, WithTableSorting;

    /**
     * فلتر الزيارة: المفتاح => عدد الشهور (null = لم يُزر أبداً).
     * التقرير يحفظ **آخر زيارة** وحدها، فمع الوقت يصير كل مقرٍّ «مُزاراً» — والسؤال
     * الذي يبقى: منذ متى؟ وفلتر المدة **يشمل ما لم يُزر أبداً** (هو متأخرٌ أيضاً).
     */
    public const VISIT_FILTERS = [
        'm3'    => 3,
        'm6'    => 6,
        'm12'   => 12,
        'never' => null,
    ];

    #[Locked]
    public string $type;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'gov', except: '')]
    public string $governorate = '';

    #[Url(as: 'visit', except: '')]
    public string $visit = '';

    public function mount(string $type): void
    {
        abort_unless(OfficeVisitReports::isType($type), 404);
        abort_unless(OfficeVisitReports::canView(auth()->user(), $type), 403);

        $this->type = $type;
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'governorate', 'visit'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'governorate', 'visit');
        $this->resetSort();
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->governorateId() !== null || $this->visitFilter() !== null;
    }

    protected function sortableColumns(): array
    {
        return [
            'name'        => 'offices.name',
            'governorate' => 'governorates.order',
            'visited'     => 'offices.' . OfficeVisitReports::column($this->type, 'visited_at'),
        ];
    }

    /** ترتيب المحافظات ثم اسم المقر — ترتيب قائمة المقرات نفسه */
    protected function defaultOrder(Builder $query): Builder
    {
        return $query->orderBy('governorates.order')->orderBy('governorates.id')->orderBy('offices.name');
    }

    /** المحافظة من الرابط — رقمٌ خالص وإلا أُهملت */
    private function governorateId(): ?int
    {
        return ctype_digit($this->governorate) ? (int) $this->governorate : null;
    }

    private function visitFilter(): ?string
    {
        return array_key_exists($this->visit, self::VISIT_FILTERS) ? $this->visit : null;
    }

    public function render()
    {
        $user    = auth()->user();
        $visited = 'offices.' . OfficeVisitReports::column($this->type, 'visited_at');

        $query = OfficeVisitReports::scopeOffices(Office::query(), $user)
            ->leftJoin('governorates', 'governorates.id', '=', 'offices.governorate_id')
            ->select('offices.*')
            ->with(['governorate:id,name', 'officeType:id,name'])
            ->when($this->search !== '', fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('offices.name') . ' LIKE ?',
                ['%' . ArabicText::normalize($this->search) . '%']
            ))
            ->when($this->governorateId(), fn ($q, $id) => $q->where('offices.governorate_id', $id))
            ->when($this->visitFilter(), function ($q, $key) use ($visited) {
                $months = self::VISIT_FILTERS[$key];
                if ($months === null) {
                    return $q->whereNull($visited);
                }
                // تاريخ الزيارة يومٌ كتبه المستخدم (لا يُحوَّل)، و«اليوم» بتوقيت القاهرة —
                // `now()` بـUTC يعطي الأمس بين ١٢ و٣ فجراً
                $cutoff = LocalTime::at(now())->startOfDay()->subMonths($months)->toDateString();

                return $q->where(fn ($q) => $q->whereNull($visited)->orWhere($visited, '<', $cutoff));
            });

        $governorates = $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get(['id', 'name'])
            : $user->governorates()->orderBy('order')->orderBy('governorates.id')->get(['governorates.id', 'governorates.name']);

        return view('livewire.offices.visit-reports.index', [
            'offices'      => $this->applySorting($query, 'offices.id')->paginate($this->perPage()),
            'title'        => __(OfficeVisitReports::REPORTS[$this->type]['list_label']),
            'visitedField' => OfficeVisitReports::column($this->type, 'visited_at'),
            'governorates' => $governorates,
        ]);
    }
}
