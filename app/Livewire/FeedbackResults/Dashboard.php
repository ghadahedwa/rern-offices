<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackDashboardExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Support\FeedbackResults\DashboardReport;
use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لوحة نتائج رأي المواطن.
 *
 * الحسابات كلها في App\Support\FeedbackResults\DashboardReport — الشاشة
 * وتقرير الـ PDF/Excel يقرآن منه معاً، فلا يخرج تقرير مطبوع بأرقام تخالف الشاشة.
 */
#[Layout('layouts.app')]
#[Title('لوحة نتائج رأي المواطن')]
class Dashboard extends Component
{
    use WithFeedbackExport, WithFeedbackFilters;

    public function mount(): void
    {
        abort_unless(FeedbackAccess::canView(Auth::user()), 403);
    }

    public function report(): DashboardReport
    {
        return new DashboardReport($this->filterSet(), Auth::user());
    }

    /** الحد الأدنى للعينة قبل ترتيب المقر. */
    public function minSample(): int
    {
        return $this->report()->minSample();
    }

    /* ── التصدير — تجميعي بالكامل، فلا خانة «تضمين بيانات المواطن» ── */

    protected function exportBaseName(): string
    {
        return 'feedback-dashboard';
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.dashboard.pdf';
    }

    public function exportHasPersonalData(): bool
    {
        return false;
    }

    public function excelExport(): object
    {
        return new FeedbackDashboardExport($this->report());
    }

    public function render()
    {
        return view('livewire.feedback-results.dashboard', $this->report()->all());
    }
}
