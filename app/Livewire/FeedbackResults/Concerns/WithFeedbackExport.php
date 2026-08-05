<?php

namespace App\Livewire\FeedbackResults\Concerns;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تصدير شاشات نتائج رأي المواطن إلى Excel و PDF.
 *
 * Excel يُبنى ويُنزَّل من المكوّن مباشرة، وPDF يُفتح في تبويب جديد عبر كنترولر
 * (نفس نمط تقارير المشروع) — والفلاتر تنتقل للكنترولر في الـ query string
 * لا في الـ session، فرابط التقرير قابل للمشاركة والحفظ.
 *
 * المكوّن المستخدِم يوفّر:
 *   exportBaseName() → اسم الملف بلا امتداد ولا تاريخ
 *   excelExport()    → كائن التصدير (يبنيه من نفس استعلام الشاشة)
 *   pdfRouteName()   → اسم راوت الـ PDF أو null لو الشاشة بلا تقرير مطبوع
 */
trait WithFeedbackExport
{
    /**
     * تضمين بيانات المواطن (الاسم/الرقم القومي/الهاتف) في الملف.
     * مقفولة افتراضياً عن قصد: الشاشة تُعرض لسوبر أدمن داخل النظام، أما الملف
     * فيخرج منه — يتبعت بالإيميل ويتنسخ. فإخراج بيانات شخصية قرار صريح لا افتراضي.
     */
    public bool $exportPersonal = false;

    abstract protected function exportBaseName(): string;

    /** عام لا محمي: الاختبارات تفحص محتوى الملف نفسه لا مجرد نجاح الاستدعاء. */
    abstract public function excelExport(): object;

    protected function pdfRouteName(): ?string
    {
        return null;
    }

    /** للقالب: الشاشات بلا تقرير مطبوع تخفي الزر. */
    public function exportHasPdf(): bool
    {
        return $this->pdfRouteName() !== null;
    }

    /** هل الشاشة تعرض بيانات شخصية أصلاً؟ (اللوحة تجميعية فلا خانة اختيار فيها) */
    public function exportHasPersonalData(): bool
    {
        return true;
    }

    /** لا معنى لتصدير نطاق فارغ — الشاشات ذات القوائم تتحقق من عدد الصفوف. */
    protected function exportIsEmpty(): bool
    {
        return false;
    }

    public function exportExcel()
    {
        $this->guardExport();

        if ($this->exportIsEmpty()) {
            Flux::toast(variant: 'warning', text: __('home.fr_export_empty'));

            return null;
        }

        $this->logExport('excel');

        return Excel::download(
            $this->excelExport(),
            $this->exportBaseName().'-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function exportPdf(): void
    {
        $this->guardExport();

        $route = $this->pdfRouteName();

        if ($route === null) {
            return;
        }

        if ($this->exportIsEmpty()) {
            Flux::toast(variant: 'warning', text: __('home.fr_export_empty'));

            return;
        }

        $this->logExport('pdf');

        // json_encode بدل التنصيص اليدوي: كلمة البحث قد تحتوي محارف تكسر سطر الـ JS
        $this->js('window.open('.json_encode(route($route, $this->exportParams())).", '_blank')");
    }

    /**
     * حالة الشاشة كاملة في الـ query string حتى يبني الكنترولر نفس الاستعلام:
     * الفلاتر والبحث والترتيب وسلة المحذوفات وقرار بيانات المواطن.
     */
    protected function exportParams(): array
    {
        $params = $this->filterSet()->toQuery();

        if (property_exists($this, 'search') && $this->search !== '') {
            $params['q'] = $this->search;
        }

        if (property_exists($this, 'showTrashed') && $this->showTrashed) {
            $params['trashed'] = 1;
        }

        if (property_exists($this, 'sortBy')) {
            $params['sort'] = $this->sortBy;
            $params['dir']  = $this->sortDir;
        }

        if ($this->exportHasPersonalData() && $this->exportPersonal) {
            $params['personal'] = 1;
        }

        return $params + $this->extraExportParams();
    }

    /** فلاتر خاصة بشاشة بعينها (سبب الرفض ونوعه مثلاً). */
    protected function extraExportParams(): array
    {
        return [];
    }

    /**
     * حارس ثانٍ: التصدير يصل في طلب مستقل عن mount، فلا يكفي فحص الدخول للشاشة.
     * نفس منطق applyBulk في WithBulkDelete.
     */
    protected function guardExport(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    /**
     * سجل مساءلة: مَن صدّر، وأي شاشة، وبأي فلتر، وهل خرجت بيانات شخصية.
     * بلا subject حتى لا يظهر في سجلّي داشبورد المقرات وإدارة النظام
     * (كلاهما يفلتر على subject_type).
     */
    protected function logExport(string $format): void
    {
        activity('feedback')
            ->causedBy(Auth::user())
            ->event('export')
            ->withProperties([
                'screen'   => $this->exportSubject(),
                'format'   => $format,
                'personal' => $this->exportHasPersonalData() && $this->exportPersonal,
                'filters'  => $this->exportParams(),
            ])
            ->log(__('home.fr_export_log').' — '.$this->exportSubject());
    }

    /** وصف الشاشة للسجل — الشاشات ذات الحذف الجماعي تعرّفه أصلاً. */
    protected function exportSubject(): string
    {
        return method_exists($this, 'bulkSubject') ? $this->bulkSubject() : __('home.fr_dashboard');
    }
}
