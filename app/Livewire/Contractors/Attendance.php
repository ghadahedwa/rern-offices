<?php

namespace App\Livewire\Contractors;

use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Office;
use App\Support\ArabicText;
use App\Support\ContractorScope;
use App\Support\Contractors\AttendanceSheet;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * شبكة تسجيل الحضور — «مقرّ × شهر» (قرار العميلة ٢٠٢٦-٠٩-٠٩).
 *
 * صفٌّ لكل عاملٍ خدم في المقر خلال الشهر، وعمودٌ لكل يوم. كل خلية تبدأ «حاضر» ولا
 * يُخزَّن إلا الاستثناء، والجمعة والعطلة والأيام خارج تسكينه في هذا المقر مقفولة.
 * المنطق كله في `AttendanceSheet`، والمكوّن يختار المقر والشهر ويحرس النطاق.
 *
 * ⚠️ صلاحيتها `contractors.attendance` لا `contractors.index` — التسجيل قد يُسنَد لمن لا
 *    يملك تعديل بيانات العاملين، والعكس.
 * ⚠️ **الحالة في المتصفح والحفظ دفعة**: طلبٌ لكل نقرة = مئات الطلبات، وانقطاعٌ في المنتصف
 *    يترك نصف الشهر محفوظاً. فالعلامات لا تمرّ بخصائص Livewire أصلاً.
 */
#[Layout('layouts.app')]
#[Title('تسجيل الحضور')]
class Attendance extends Component
{
    #[Url(as: 'gov', except: '')]
    public string $governorate = '';

    #[Url(as: 'office', except: '')]
    public string $office = '';

    /** 'Y-m' — الفارغ = الشهر الحالي بتوقيت القاهرة. */
    #[Url(as: 'month', except: '')]
    public string $month = '';

    /** فلتر «العامل» داخل الشبكة — تضييقُ صفوفٍ لا شاشةٌ أخرى. */
    #[Url(as: 'worker', except: '')]
    public string $worker = '';

    /** بحث بالاسم عابرٌ للمقارّ — يفتح الشبكة على مقرّ العامل. */
    public string $search = '';

    /**
     * يزيد مع كل حفظ فيتغيّر مفتاح الشبكة وتُبنى حالتها من جديد من الداتابيز.
     * ⚠️ لا تدخل البصمة في المفتاح: تحديث الـkeepalive بعد حفظ زميلٍ كان سيمحو
     *    تغييرات المستخدم غير المحفوظة بلا إنذار — والرفض عند الحفظ هو الإنذار.
     */
    public int $version = 0;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        // الشهر ظاهرٌ في الرابط دائماً — الرابط المنسوخ يفتح الشهر نفسه لا شهرَ يوم فتحه
        $this->month = $this->monthStart()->format('Y-m');

        // رابطٌ فيه المقر وحده (من البحث أو منسوخاً) يكمل المحافظة من المقر
        if ($this->governorate === '' && ($office = $this->selectedOffice())) {
            $this->governorate = (string) $office->governorate_id;
        }
    }

    public function updatedGovernorate(): void
    {
        $this->office = '';
        $this->worker = '';
    }

    public function updatedOffice(): void
    {
        $this->worker = '';
    }

    /** قيمة تالفة أو ممسوحة من حقل الشهر تعود للشهر الحالي بدل حقلٍ فارغ فوق شبكةٍ معروضة. */
    public function updatedMonth(): void
    {
        $this->month = $this->monthStart()->format('Y-m');
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthStart()->addMonths($delta > 0 ? 1 : -1)->format('Y-m');
    }

    /** يفتح الشبكة على مقرّ العامل — التسكين الواقع في الشهر المعروض، وإلا آخر تسكين. */
    public function openWorker(int $id): void
    {
        $contractor = ContractorScope::applyToContractors(Contractor::whereKey($id))->first();

        if (! $contractor) {
            return;
        }

        $month = $this->monthStart();

        $assignment = $contractor->assignments()
            ->overlapping($month, $month->endOfMonth())
            ->orderByDesc('started_on')
            ->first()
            ?? $contractor->assignments()->orderByDesc('started_on')->first();

        // ⚠️ التسكين الأخير قد يكون في مقرٍّ خارج النطاق (النقل مفتوح على الجمهورية)
        if (! $assignment || ! ContractorScope::allowsOffice($assignment->office_id)) {
            Flux::toast(variant: 'warning', text: __('home.ct_att_worker_out_of_scope'));

            return;
        }

        // التسكين خارج الشهر المعروض ← يُفتح على أقرب شهرٍ خدم فيه، لا على شبكةٍ لا يظهر فيها
        if ($assignment->started_on->gt($month->endOfMonth())) {
            $this->month = $assignment->started_on->format('Y-m');
        } elseif ($assignment->ended_on && $assignment->ended_on->lt($month)) {
            $this->month = $assignment->ended_on->format('Y-m');
        }

        $this->governorate = (string) $assignment->office->governorate_id;
        $this->office      = (string) $assignment->office_id;
        $this->worker      = (string) $contractor->id;
        $this->search      = '';
    }

    /** «تجاهل التغييرات»: يُعاد بناء الشبكة من الداتابيز — وبعد رفض الحفظ يحمّل تعديل الزميل. */
    public function reload(): void
    {
        $this->version++;
    }

    /**
     * @param  array  $marks     [contractorId => ['Y-m-d' => statusId]]
     * @param  array  $reviewed  [contractorId => bool]
     */
    public function save(array $marks, array $reviewed, string $fingerprint): void
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        $sheet = $this->sheet();

        // ⚠️ المقر يصل من الرابط — والنطاق يُفحص عند الحفظ لا عند العرض وحده
        abort_unless($sheet !== null, 403);

        $result = $sheet->save($marks, $reviewed, $fingerprint, Auth::user());

        if ($result === AttendanceSheet::STALE) {
            Flux::toast(variant: 'danger', duration: 10000, text: __('home.ct_att_stale'));

            return;
        }

        $this->version++;

        Flux::toast(variant: 'success', text: __('home.ct_att_saved', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'deleted' => $result['deleted'],
        ]));
    }

    private function monthStart(): CarbonImmutable
    {
        return AttendanceSheet::parseMonth($this->month) ?? WorkingDays::today()->startOfMonth();
    }

    private function governorateId(): ?int
    {
        return ctype_digit($this->governorate) ? (int) $this->governorate : null;
    }

    /**
     * ⚠️ المقر من الرابط يُقرأ عبر النطاق — ومقرٌّ لا ينتمي للمحافظة المختارة يُهمَل.
     */
    private function selectedOffice(): ?Office
    {
        if (! ctype_digit($this->office) || ! ContractorScope::allowsOffice((int) $this->office)) {
            return null;
        }

        $office = Office::find((int) $this->office);

        if ($office && $this->governorateId() !== null && (int) $office->governorate_id !== $this->governorateId()) {
            return null;
        }

        return $office;
    }

    private function sheet(): ?AttendanceSheet
    {
        $office = $this->selectedOffice();

        return $office ? new AttendanceSheet($office, $this->monthStart()) : null;
    }

    /**
     * مقرات المنسدلة: في النطاق وفي المحافظة، **وخدم فيها عاملٌ خلال الشهر**.
     * ⚠️ مقرٌّ بلا عاملين يفتح شبكةً فارغة توهم أن الشاشة معطّلة.
     */
    private function officeOptions(): \Illuminate\Support\Collection
    {
        $governorateId = $this->governorateId();

        if ($governorateId === null) {
            return collect();
        }

        $month = $this->monthStart();

        $withWorkers = array_flip(ContractorAssignment::query()
            ->overlapping($month, $month->endOfMonth())
            ->whereHas('office', fn ($q) => $q->where('governorate_id', $governorateId))
            ->distinct()
            ->pluck('office_id')
            ->map(fn ($id) => (int) $id)
            ->all());

        return ContractorScope::officeOptions($governorateId)
            ->filter(fn (Office $office) => isset($withWorkers[$office->id]))
            ->values();
    }

    private function searchResults(): \Illuminate\Support\Collection
    {
        if (mb_strlen(trim($this->search)) < 2) {
            return collect();
        }

        return ContractorScope::applyToContractors(Contractor::query())
            ->where(function ($q) {
                $q->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ['%'.ArabicText::normalize($this->search).'%'])
                    ->orWhere('phone', 'like', '%'.trim($this->search).'%');
            })
            ->with(['currentAssignment.office'])
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        $sheet  = $this->sheet();
        $rows   = $sheet?->rows() ?? [];
        $worker = ctype_digit($this->worker) ? (int) $this->worker : null;

        // عاملٌ في الرابط ليس في هذه الشبكة يُهمَل بدل أن يُخرج شبكةً فارغة
        if ($worker !== null && ! collect($rows)->contains('id', $worker)) {
            $worker = null;
        }

        $month = $this->monthStart();

        return view('livewire.contractors.attendance', [
            'governorates'  => ContractorScope::governorateOptions(),
            'offices'       => $this->officeOptions(),
            'sheet'         => $sheet,
            'rows'          => $rows,
            'workerId'      => $worker,
            'columns'       => $sheet?->columns() ?? [],
            'breakdown'     => WorkingDays::breakdown($month, $month->endOfMonth()),
            'holidays'      => WorkingDays::holidayMap($month, $month->endOfMonth()),
            'fingerprint'   => $sheet?->fingerprint($rows) ?? '',
            'statuses'      => AttendanceStatus::markable()->ordered()->get(['id', 'name', 'color']),
            // الحالات المعطَّلة الباقية على خلايا قديمة تحتاج لونها واسمها للعرض
            'allStatuses'   => AttendanceStatus::query()->get(['id', 'name', 'color', 'is_default']),
            'monthValue'    => $month->format('Y-m'),
            'monthLabel'    => $month->locale('ar')->translatedFormat('F Y'),
            'searchResults' => $this->searchResults(),
        ]);
    }
}
