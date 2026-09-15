<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackDigitalSummaryExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Support\FeedbackResults\DigitalReport;
use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * «ملخص المنصات» — صفحة مستقلة (قرار المستخدمة)، لا قسم في اللوحة الرئيسية.
 * الحسابات كلها في DigitalReport، والشاشة والتقريران يقرؤون منه معاً.
 */
#[Layout('layouts.app')]
#[Title('ملخص المنصات الرقمية')]
class DigitalSummary extends Component
{
    use WithFeedbackExport, WithFeedbackFilters;

    /** اتجاه مقارنة المقرات بنسبة الحجز — الأعلى أولاً أو الأقل أولاً. */
    #[Url(as: 'order', except: 'desc')]
    public string $officeOrder = 'desc';

    public function mount(): void
    {
        abort_unless(FeedbackAccess::canView(Auth::user()), 403);
    }

    public function report(): DigitalReport
    {
        return new DigitalReport($this->filterSet(), Auth::user());
    }

    public function toggleOfficeOrder(): void
    {
        $this->officeOrder = $this->safeOfficeOrder() === 'desc' ? 'asc' : 'desc';
    }

    /** القيمة تصل من الرابط — المجهولة تسقط إلى الافتراضي. */
    public function safeOfficeOrder(): string
    {
        return $this->officeOrder === 'asc' ? 'asc' : 'desc';
    }

    /* ── التصدير — تجميعي بالكامل، فلا خانة «تضمين بيانات المواطن» ── */

    protected function exportBaseName(): string
    {
        return 'feedback-digital-summary';
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.digital-summary.pdf';
    }

    public function exportHasPersonalData(): bool
    {
        return false;
    }

    protected function exportSubject(): string
    {
        return __('home.fr_dg_summary');
    }

    protected function extraExportParams(): array
    {
        return ['order' => $this->safeOfficeOrder()];
    }

    public function excelExport(): object
    {
        return new FeedbackDigitalSummaryExport($this->report(), $this->safeOfficeOrder());
    }

    public function render()
    {
        return view('livewire.feedback-results.digital-summary', $this->report()->all($this->safeOfficeOrder()));
    }
}
