<?php

namespace App\Livewire\DataEntry\Operators;

use App\Models\DataEntryOperator;
use App\Models\Governorate;
use App\Support\DataEntry\OperatorsImport;
use App\Support\DataEntry\OperatorsTemplate;
use App\Support\DataEntryScope;
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
#[Title('استيراد مدخلي بيانات')]
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
        abort_unless(Auth::user()?->can('data-entry.create'), 403);

        $this->startedOn = WorkingDays::today()->toDateString();
    }

    private function guard(): void
    {
        abort_unless(Auth::user()?->can('data-entry.create'), 403);
    }

    /** المحافظة المختارة بعد فحص النطاق — أو null. */
    private function scopedGovernorate(): ?Governorate
    {
        if (! ctype_digit($this->governorate)) {
            return null;
        }

        $ids = DataEntryScope::governorateIds();
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
            $this->addError('governorate', __('home.de_import_pick_governorate'));

            return null;
        }

        $offices = DataEntryScope::officeOptions($governorate->id);

        if ($offices->isEmpty()) {
            $this->addError('governorate', __('home.de_import_no_offices'));

            return null;
        }

        $template = new OperatorsTemplate($governorate, $offices);
        $path     = tempnam(sys_get_temp_dir(), 'de_tpl_').'.xlsx';

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
            $this->addError('governorate', __('home.de_import_pick_governorate'));

            return;
        }

        $reader     = new OperatorsImport($governorate, DataEntryScope::officeOptions($governorate->id));
        $this->rows = $reader->parse($this->file->getRealPath());
        $this->parsed = true;
    }

    /** عدد الصفوف حسب الحالة — للشريط أعلى الجدول ولزرّ الحفظ. */
    public function counts(): array
    {
        $rows = collect($this->rows);

        return [
            'ok'        => $rows->where('status', OperatorsImport::STATUS_OK)->count(),
            'duplicate' => $rows->where('status', OperatorsImport::STATUS_DUPLICATE)->count(),
            'error'     => $rows->where('status', OperatorsImport::STATUS_ERROR)->count(),
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
            $this->addError('governorate', __('home.de_import_pick_governorate'));

            return;
        }

        $this->validate(['startedOn' => ['required', 'date']]);

        $valid = collect($this->rows)->where('status', OperatorsImport::STATUS_OK);

        if ($valid->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('home.de_import_nothing'));

            return;
        }

        // ⚠️ المقر يُعاد فحصه على النطاق قبل الحفظ — الصفوف تعيش في حالة المكوّن
        //    بين طلبين، فقيمتها ليست موثوقة كما لو قُرئت الآن.
        $allowed = DataEntryScope::officeOptions($governorate->id)->pluck('id')->all();

        $created = 0;

        DB::transaction(function () use ($valid, $allowed, &$created) {
            foreach ($valid as $row) {
                if (! in_array($row['office_id'], $allowed, true)) {
                    continue;
                }

                $operator = DataEntryOperator::create([
                    'name'  => $row['name'],
                    'phone' => $row['phone'] !== '' ? $row['phone'] : null,
                ]);

                $operator->assignments()->create([
                    'office_id'  => $row['office_id'],
                    'started_on' => $this->startedOn,
                ]);

                $created++;
            }
        });

        Flux::toast(variant: 'success', text: __('home.de_import_done', ['count' => $created]));

        $this->redirect(route('data-entry.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.data-entry.operators.import', [
            'governorates' => DataEntryScope::governorateOptions(),
            'counts'       => $this->counts(),
        ]);
    }
}
