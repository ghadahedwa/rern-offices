<?php

namespace App\Livewire\Contractors;

use App\Models\Contractor;
use App\Models\Profession;
use App\Models\Governorate;
use App\Support\Contractors\ContractorsImport;
use App\Support\Contractors\ContractorsTemplate;
use App\Support\ContractorScope;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * تسكين دفعة مدخلين من ملف Excel — قالبٌ لكل محافظة يُرسَل للمفتش فيملؤه.
 *
 * ⚠️ **القالب يُبنى لحظة الطلب** من مقرات المحافظة، فمقرٌّ يُضاف اليوم يظهر فيه غداً.
 * ⚠️ **المحافظة تُفحص على النطاق** في التنزيل والاستيراد معاً — القيمة تصل من العميل،
 *    وقالبُ محافظةٍ ليست للمستخدم يسرّب أسماء مقراتها.
 * ⚠️ **العرض قبل الحفظ**: الملف يُقرأ ويُفحص ثم تُعرض نتيجته، ولا يُحفظ إلا بضغطة
 *    ثانية — فصفٌّ خاطئ لا يوقف الباقي، والمستخدم يرى ما سيدخل قبل أن يقع.
 */
#[Layout('layouts.app')]
#[Title('استيراد عاملين')]
class Import extends Component
{
    use WithFileUploads;

    public string $governorate = '';

    public $file = null;

    public string $startedOn = '';

    /** نتيجة قراءة الملف — تُعرض ثم تُحفظ بموافقة. */
    public array $rows = [];

    public bool $parsed = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('contractors.create'), 403);

        $this->startedOn = WorkingDays::today()->toDateString();
    }

    private function guard(): void
    {
        abort_unless(Auth::user()?->can('contractors.create'), 403);
    }

    /** المحافظة المختارة بعد فحص النطاق — أو null. */
    private function scopedGovernorate(): ?Governorate
    {
        if (! ctype_digit($this->governorate)) {
            return null;
        }

        $ids = ContractorScope::governorateIds();
        $id  = (int) $this->governorate;

        if ($ids !== null && ! in_array($id, $ids, true)) {
            return null;
        }

        return Governorate::find($id);
    }

    public function updatedGovernorate(): void
    {
        // القالب تغيّر، فما قُرئ من ملفٍّ سابق لم يعد يخصّ هذه المحافظة
        $this->reset('file', 'rows', 'parsed');
        $this->resetValidation();
    }

    public function downloadTemplate()
    {
        $this->guard();

        $governorate = $this->scopedGovernorate();

        if (! $governorate) {
            $this->addError('governorate', __('home.ct_import_pick_governorate'));

            return null;
        }

        $offices = ContractorScope::officeOptions($governorate->id);

        if ($offices->isEmpty()) {
            $this->addError('governorate', __('home.ct_import_no_offices'));

            return null;
        }

        $template = new ContractorsTemplate($governorate, $offices);
        $path     = tempnam(sys_get_temp_dir(), 'ct_tpl_').'.xlsx';

        $template->saveTo($path);

        return response()->download($path, $template->filename())->deleteFileAfterSend();
    }

    public function updatedFile(): void
    {
        $this->guard();

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $governorate = $this->scopedGovernorate();

        if (! $governorate) {
            $this->addError('governorate', __('home.ct_import_pick_governorate'));

            return;
        }

        $reader     = new ContractorsImport($governorate, ContractorScope::officeOptions($governorate->id));
        $this->rows = $reader->parse($this->file->getRealPath());
        $this->parsed = true;
    }

    /** عدد الصفوف حسب الحالة — للشريط أعلى الجدول ولزرّ الحفظ. */
    public function counts(): array
    {
        $rows = collect($this->rows);

        return [
            'ok'        => $rows->where('status', ContractorsImport::STATUS_OK)->count(),
            'duplicate' => $rows->where('status', ContractorsImport::STATUS_DUPLICATE)->count(),
            'error'     => $rows->where('status', ContractorsImport::STATUS_ERROR)->count(),
        ];
    }

    public function import(): void
    {
        $this->guard();

        // ⚠️ لا يُحفظ إلا ما عُرض: النداء يصل في طلب مستقل بلا قراءة ملف
        if (! $this->parsed) {
            return;
        }

        $governorate = $this->scopedGovernorate();

        if (! $governorate) {
            $this->addError('governorate', __('home.ct_import_pick_governorate'));

            return;
        }

        $this->validate(['startedOn' => ['required', 'date']]);

        $valid = collect($this->rows)->where('status', ContractorsImport::STATUS_OK);

        if ($valid->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('home.ct_import_nothing'));

            return;
        }

        // ⚠️ المقر يُعاد فحصه على النطاق قبل الحفظ — الصفوف تعيش في حالة المكوّن
        //    بين طلبين، فقيمتها ليست موثوقة كما لو قُرئت الآن.
        $allowed = ContractorScope::officeOptions($governorate->id)->pluck('id')->all();

        // ⚠️ والصفة كذلك تُعاد قراءتها من الجدول: صفةٌ عُطِّلت أو حُذفت بين المعاينة
        //    والحفظ لا تُكتب على عامل — والصفّ يسقط بلا صفة بدل أن يفشل الحفظ كله.
        $professions = Profession::selectable()->pluck('id')->all();

        $created = 0;

        DB::transaction(function () use ($valid, $allowed, $professions, &$created) {
            foreach ($valid as $row) {
                if (! in_array($row['office_id'], $allowed, true)) {
                    continue;
                }

                $contractor = Contractor::create([
                    'name'          => $row['name'],
                    'profession_id' => in_array($row['profession_id'] ?? null, $professions, true)
                        ? $row['profession_id']
                        : null,
                    'phone'         => $row['phone'] !== '' ? $row['phone'] : null,
                ]);

                $contractor->assignments()->create([
                    'office_id'  => $row['office_id'],
                    'started_on' => $this->startedOn,
                ]);

                $created++;
            }
        });

        Flux::toast(variant: 'success', text: __('home.ct_import_done', ['count' => $created]));

        $this->redirect(route('contractors.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.contractors.import', [
            'governorates' => ContractorScope::governorateOptions(),
            'counts'       => $this->counts(),
        ]);
    }
}
