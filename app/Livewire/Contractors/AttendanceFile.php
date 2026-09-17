<?php

namespace App\Livewire\Contractors;

use App\Models\Governorate;
use App\Support\ContractorScope;
use App\Support\Contractors\AttendanceMonthFile;
use App\Support\Contractors\AttendanceSheet;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * كشف الشهر بالإكسيل — صفحةٌ مستقلة عن شبكة التسجيل (طلب العميلة ٢٠٢٦-٠٩-١٧).
 *
 * محافظة ← شهر ← تنزيل الكشف · رفعه مملوءاً ← معاينة ← حفظ. المنطق كله في
 * `AttendanceMonthFile` فوق `AttendanceSheet`، فالملف والشبكة نافذتان على حالةٍ واحدة.
 *
 * ⚠️ صلاحيتها `contractors.attendance` كالشبكة — الملف طريقٌ ثانٍ للتسجيل نفسه.
 * ⚠️ المحافظة تصل من الرابط، فتُفحص على النطاق في التنزيل والرفع والحفظ.
 */
#[Layout('layouts.app')]
#[Title('كشف الشهر بالإكسيل')]
class AttendanceFile extends Component
{
    use WithFileUploads;

    #[Url(as: 'gov', except: '')]
    public string $governorate = '';

    /** 'Y-m' — الفارغ = الشهر الحالي بتوقيت القاهرة. */
    #[Url(as: 'month', except: '')]
    public string $month = '';

    public $monthFile = null;

    /**
     * نتيجة قراءة الملف وبصمات مقراته لحظة المعاينة.
     * ⚠️ `Locked`: البصمات تحرس الحفظ من تعديل زميلٍ بعد المعاينة، فلا تُترك للعميل يعدّلها.
     *    والعلامات نفسها **لا تُحفظ في حالة المكوّن** — الملف يُعاد قراءته عند الحفظ.
     */
    #[Locked]
    public array $filePreview = [];

    #[Locked]
    public array $fileFingerprints = [];

    /** آخر حفظ ناجح — لرابط «افتح الكشف على الشاشة». */
    #[Locked]
    public array $savedResult = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        $this->month = $this->monthStart()->format('Y-m');
    }

    public function updatedGovernorate(): void
    {
        $this->cancelMonthFile();
    }

    public function updatedMonth(): void
    {
        $this->month = $this->monthStart()->format('Y-m');
        $this->cancelMonthFile();
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthStart()->addMonths($delta > 0 ? 1 : -1)->format('Y-m');
        $this->cancelMonthFile();
    }

    private function monthStart(): CarbonImmutable
    {
        return AttendanceSheet::parseMonth($this->month) ?? WorkingDays::today()->startOfMonth();
    }

    /** المحافظة المختارة بعد فحص النطاق — القيمة تصل من الرابط. */
    private function scopedGovernorate(): ?Governorate
    {
        if (! ctype_digit($this->governorate)) {
            return null;
        }

        return ContractorScope::governorateOptions()->firstWhere('id', (int) $this->governorate)
            ? Governorate::find((int) $this->governorate)
            : null;
    }

    private function monthFile(): ?AttendanceMonthFile
    {
        $governorate = $this->scopedGovernorate();

        return $governorate ? new AttendanceMonthFile($governorate, $this->monthStart()) : null;
    }

    public function downloadMonthFile()
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        $file = $this->monthFile();

        if (! $file || $file->sheets() === []) {
            Flux::toast(variant: 'warning', text: __('home.ct_att_no_offices'));

            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'ct_att_').'.xlsx';
        $file->saveTo($path);

        return response()->download($path, $file->filename())->deleteFileAfterSend();
    }

    public function updatedMonthFile(): void
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        $this->reset('filePreview', 'fileFingerprints', 'savedResult');
        $this->validate(['monthFile' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);

        $file = $this->monthFile();
        abort_unless($file !== null, 403);

        $parsed = $file->parse($this->monthFile->getRealPath());

        $this->filePreview = [
            'error'   => $parsed['error'],
            'errors'  => $parsed['errors'],
            'ignored' => $parsed['ignored'],
            'summary' => $parsed['error'] ? null : $file->summarize($parsed),
            'label'   => $file->monthLabel().' — '.$file->governorate->name,
        ];

        $this->fileFingerprints = $parsed['error'] ? [] : $file->fingerprints(array_keys($parsed['offices']));
    }

    public function importMonthFile(): void
    {
        abort_unless(Auth::user()?->can('contractors.attendance'), 403);

        // ⚠️ لا يُحفظ إلا ما عُرض: النداء يصل في طلبٍ مستقل
        if (! $this->monthFile || ! ($this->filePreview['summary'] ?? null)) {
            return;
        }

        $file = $this->monthFile();
        abort_unless($file !== null, 403);

        // ⚠️ الملف يُعاد قراءته الآن لا من حالة المكوّن — وما في المتصفح لا يُوثَق به
        $parsed = $file->parse($this->monthFile->getRealPath());

        if ($parsed['error'] || $parsed['offices'] === []) {
            $this->cancelMonthFile();

            return;
        }

        $result = $file->apply($parsed, $this->fileFingerprints, Auth::user());

        if ($result === AttendanceSheet::STALE) {
            $this->cancelMonthFile();
            Flux::toast(variant: 'danger', duration: 10000, text: __('home.ct_att_file_stale'));

            return;
        }

        $this->cancelMonthFile();
        $this->savedResult = $result;

        Flux::toast(variant: 'success', text: __('home.ct_att_saved', $result));
    }

    public function cancelMonthFile(): void
    {
        $this->reset('monthFile', 'filePreview', 'fileFingerprints', 'savedResult');
        $this->resetValidation('monthFile');
    }

    public function render()
    {
        $month       = $this->monthStart();
        $governorate = $this->scopedGovernorate();

        return view('livewire.contractors.attendance-file', [
            'governorates' => ContractorScope::governorateOptions(),
            'monthLabel'   => $month->locale('ar')->translatedFormat('F Y'),
            'fileLabel'    => $governorate ? $month->locale('ar')->translatedFormat('F Y').' — '.$governorate->name : null,
        ]);
    }
}
