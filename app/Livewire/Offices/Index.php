<?php

namespace App\Livewire\Offices;

use App\Models\BuffetService;
use App\Models\ConnectionType;
use App\Models\ContractualStatus;
use App\Models\DisabilitieAccess;
use App\Models\DocumentPhotocopyingService;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\MicrofilmOption;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\StructuralCondition;
use App\Models\WorkingHour;
use App\Models\WorkSystem;
use App\Livewire\Concerns\RemembersListUrl;
use App\Support\OfficeScope;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
#[Title('المقرات')]
class Index extends Component
{
    use WithPagination, WithPerPage, WithTableSorting, RemembersListUrl;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url]
    public ?int $governorate_id = null;

    #[Url]
    public ?int $type_id = null;

    #[Url]
    public ?int $location_description_id = null;

    /** إظهار المقرات الظاهرة للمواطن فقط (toggle مثل VIP) */
    #[Url]
    public bool $public_only = false;

    #[Url]
    public bool $needs_visit = false;

    #[Url]
    public bool $is_vip = false;

    /** لوحة البحث المتقدم — مقفولة افتراضياً، خياراتها لا تُحمَّل إلا عند فتحها */
    public bool $showAdvanced = false;

    // ── فلاتر البحث المتقدم (كلها single select) — في الرابط حتى يرجع إليها زرّ الرجوع ──
    #[Url(as: 'ws')]
    public ?int $work_system_id = null;
    #[Url(as: 'wh')]
    public ?int $working_hours_id = null;
    #[Url(as: 'wd')]
    public ?string $working_days = null;
    #[Url(as: 'cs')]
    public ?int $contractual_status_id = null;
    #[Url(as: 'sc')]
    public ?int $structural_condition_id = null;
    #[Url(as: 'ct')]
    public ?int $connection_type_id = null;
    #[Url(as: 'mf')]
    public ?int $microfilm_option_id = null;
    #[Url(as: 'da')]
    public ?int $disabilities_access_id = null;
    #[Url(as: 'dp')]
    public ?int $document_photocopying_service_id = null;
    #[Url(as: 'bs')]
    public ?int $buffet_service_id = null;
    #[Url(as: 'cam')]
    public ?string $surveillance_cameras = null;
    #[Url(as: 'qms')]
    public ?string $queue_management_system = null;

    /** أسماء فلاتر البحث المتقدم — للتصفير عند إغلاق اللوحة */
    protected function advancedKeys(): array
    {
        return [
            'work_system_id', 'working_hours_id', 'working_days',
            'contractual_status_id', 'structural_condition_id', 'connection_type_id',
            'microfilm_option_id', 'disabilities_access_id',
            'document_photocopying_service_id', 'buffet_service_id',
            'surveillance_cameras', 'queue_management_system',
        ];
    }

    protected function hasAdvancedFilters(): bool
    {
        foreach ($this->advancedKeys() as $key) {
            if ($this->{$key} !== null && $this->{$key} !== '') {
                return true;
            }
        }

        return false;
    }

    /** فتح/إغلاق لوحة البحث المتقدم — الإغلاق يصفّر فلاتره حتى لا تبقى فلاتر خفية مُطبَّقة */
    public function toggleAdvanced(): void
    {
        $this->showAdvanced = ! $this->showAdvanced;

        if (! $this->showAdvanced) {
            $this->reset($this->advancedKeys());
            $this->resetPage();
        }
    }

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.index'),
            403
        );

        // رجوعٌ برابطٍ فيه فلاتر متقدمة: اللوحة تُفتح عليها — فلترٌ مُطبَّق في لوحةٍ مقفولة لا يراه المستخدم
        $this->showAdvanced = $this->hasAdvancedFilters();
    }

    /** رابط الرجوع من صفحات المقر (`RemembersListUrl`) */
    protected function listReturnRoute(): string
    {
        return 'offices.index';
    }

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    protected function canDeleteOffices(): bool
    {
        return auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.delete');
    }

    public function askDelete(int $id): void
    {
        abort_unless($this->canDeleteOffices(), 403);
        $office = Office::findOrFail($id);
        // المعرّف يصل من العميل — مقرٌّ خارج النطاق لا يُحذف بكتابة رقمه
        abort_unless(OfficeScope::allows(auth()->user(), $office), 403);
        $this->deletingId    = $office->id;
        $this->deletingLabel = $office->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless($this->canDeleteOffices(), 403);
        if ($this->deletingId) {
            $office = Office::findOrFail($this->deletingId);
            abort_unless(OfficeScope::allows(auth()->user(), $office), 403);
            $office->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.office_deleted'));
        }
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingGovernorateId(): void { $this->resetPage(); }
    public function updatingTypeId(): void { $this->resetPage(); }
    public function updatingLocationDescriptionId(): void { $this->resetPage(); }
    public function updatingPublicOnly(): void { $this->resetPage(); }
    public function updatingNeedsVisit(): void { $this->resetPage(); }
    public function updatingWorkSystemId(): void { $this->resetPage(); }
    public function updatingWorkingHoursId(): void { $this->resetPage(); }
    public function updatingWorkingDays(): void { $this->resetPage(); }
    public function updatingContractualStatusId(): void { $this->resetPage(); }
    public function updatingStructuralConditionId(): void { $this->resetPage(); }
    public function updatingConnectionTypeId(): void { $this->resetPage(); }
    public function updatingMicrofilmOptionId(): void { $this->resetPage(); }
    public function updatingDisabilitiesAccessId(): void { $this->resetPage(); }
    public function updatingDocumentPhotocopyingServiceId(): void { $this->resetPage(); }
    public function updatingBuffetServiceId(): void { $this->resetPage(); }
    public function updatingSurveillanceCameras(): void { $this->resetPage(); }
    public function updatingQueueManagementSystem(): void { $this->resetPage(); }

    /**
     * أعمدة الترتيب: مفتاح الرابط => عمود أو تعبير SQL.
     * ⚠️ أعمدة الجداول المرتبطة **استعلامات فرعية لا joins**: الـjoin يجعل `name` و`created_at`
     *    ملتبسين في فلاتر الشاشة القائمة (البحث مثلاً)، والاستعلام الفرعي لا يمسّها.
     *    والمحافظة بترتيبها المعتمد (`order`) كمنسدلة المحافظات لا أبجدياً.
     */
    protected function sortableColumns(): array
    {
        $related = fn (string $table, string $column, string $foreignKey) => [DB::raw(sprintf(
            '(select %s from %s where %s = %s)',
            DB::getQueryGrammar()->wrap("{$table}.{$column}"),
            DB::getQueryGrammar()->wrap($table),
            DB::getQueryGrammar()->wrap("{$table}.id"),
            DB::getQueryGrammar()->wrap("offices.{$foreignKey}"),
        ))];

        return [
            'name'        => 'offices.name',
            'governorate' => $related('governorates', 'order', 'governorate_id'),
            'type'        => $related('office_types', 'name', 'type_id'),
            'location'    => $related('location_descriptions', 'name', 'location_description_id'),
            'connection'  => $related('connection_types', 'name', 'connection_type_id'),
        ];
    }

    /** الأحدث إضافةً أولاً — ترتيب الشاشة قبل إضافة الترتيب */
    protected function defaultOrder(Builder $query): Builder
    {
        return $query->orderByDesc('offices.created_at');
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        // النطاق كله (المحافظات + قيد النوع/الوصف على الدور) من مصدره الواحد
        $query = OfficeScope::apply(
            Office::with(['governorate', 'officeType', 'locationDescription', 'connectionType']),
            $user,
        )
            ->when($this->governorate_id, fn($q) => $q->where('governorate_id', $this->governorate_id))
            ->when($this->type_id, fn($q) => $q->where('type_id', $this->type_id))
            ->when($this->location_description_id, fn($q) => $q->where('location_description_id', $this->location_description_id))
            ->when($this->public_only, fn($q) => $q->publicFeedback())
            ->when($this->search, fn($q) => $q->whereRaw(
                \App\Support\ArabicText::sqlNormalize('name').' LIKE ?',
                ['%'.\App\Support\ArabicText::normalize($this->search).'%']
            ))
            ->when($this->needs_visit, fn($q) => $q->where(fn($q) => $q
                ->whereNull('visited_at')
                ->orWhere('visited_at', '<', now()->subMonths(6))
            ))
            ->when($this->is_vip, fn($q) => $q->where('is_vip', true))
            // ── فلاتر البحث المتقدم ──
            ->when($this->work_system_id, fn($q) => $q->where('work_system_id', $this->work_system_id))
            ->when($this->working_hours_id, fn($q) => $q->where('working_hours_id', $this->working_hours_id))
            ->when($this->working_days, fn($q) => $q->where('working_days', $this->working_days))
            ->when($this->contractual_status_id, fn($q) => $q->where('contractual_status_id', $this->contractual_status_id))
            ->when($this->structural_condition_id, fn($q) => $q->where('structural_condition_id', $this->structural_condition_id))
            ->when($this->connection_type_id, fn($q) => $q->where('connection_type_id', $this->connection_type_id))
            ->when($this->microfilm_option_id, fn($q) => $q->where('microfilm_option_id', $this->microfilm_option_id))
            ->when($this->disabilities_access_id, fn($q) => $q->where('disabilities_access_id', $this->disabilities_access_id))
            ->when($this->document_photocopying_service_id, fn($q) => $q->where('document_photocopying_service_id', $this->document_photocopying_service_id))
            ->when($this->buffet_service_id, fn($q) => $q->where('buffet_service_id', $this->buffet_service_id))
            ->when($this->surveillance_cameras, fn($q) => $q->where('surveillance_cameras', $this->surveillance_cameras))
            ->when($this->queue_management_system, fn($q) => $q->where('queue_management_system', $this->queue_management_system));

        $query = $this->applySorting($query, 'offices.id', 'desc');

        $allowedTypeIds = OfficeScope::allowedTypeIds($user);
        $allowedDescIds = OfficeScope::allowedDescriptionIds($user);

        // خيارات البحث المتقدم تُحمَّل فقط عند فتح اللوحة (إبقاء التحميل الأولي خفيفاً)
        $advancedOptions = $this->showAdvanced ? [
            'workSystems'         => WorkSystem::orderBy('name')->get(),
            'workingHoursOptions' => WorkingHour::orderBy('name')->get(),
            'contractualStatuses' => ContractualStatus::orderBy('name')->get(),
            'structuralConditions'=> StructuralCondition::orderBy('name')->get(),
            'connections'         => ConnectionType::orderBy('name')->get(),
            'microfilmOptions'    => MicrofilmOption::orderBy('name')->get(),
            'disabilitiesAccess'  => DisabilitieAccess::orderBy('name')->get(),
            'photocopyingOptions' => DocumentPhotocopyingService::orderBy('name')->get(),
            'buffetOptions'       => BuffetService::orderBy('name')->get(),
        ] : [];

        return view('livewire.offices.index', [
            'offices'               => $query->paginate($this->perPage()),
            'governorates'          => $governorates,
            // منسدلتا الفلتر بما يقع في نطاقه وحده — خيارٌ لا مقرات خلفه يُوهم بشاشة معطّلة
            'officeTypes'           => OfficeType::when($allowedTypeIds !== null, fn ($q) => $q->whereIn('id', $allowedTypeIds))->orderBy('name')->get(),
            'locationDescriptions'  => LocationDescription::when($allowedDescIds !== null, fn ($q) => $q->whereIn('id', $allowedDescIds))->orderBy('name')->get(),
            ...$advancedOptions,
            'isSuperAdmin' => $isSuperAdmin,
            'canCreate'    => $isSuperAdmin || $user?->can('offices.create'),
            'canView'      => $isSuperAdmin || $user?->can('offices.view'),
            'canEdit'      => $isSuperAdmin || $user?->can('offices.edit'),
            'canDelete'    => $isSuperAdmin || $user?->can('offices.delete'),
        ]);
    }
}
