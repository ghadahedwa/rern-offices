<?php

namespace App\Livewire\FeedbackResults;

use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackRating;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('تقييمات المواطنين')]
class Ratings extends Component
{
    use WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** الصف المفتوح لعرض التفاصيل (null = مفيش) */
    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    protected function sortableColumns(): array
    {
        return ['created_at', 'overall_rating'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function render()
    {
        $ratings = $this->applyFilters(FeedbackRating::query())
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        // نص الملاحظة الحر: أغنى ما في البيانات، ومطبَّع عربياً مثل الاسم
                        ->orWhereRaw(ArabicText::sqlNormalize('notes').' LIKE ?', ["%{$norm}%"])
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->with(['office:id,name', 'governorate:id,name'])
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.ratings', [
            'ratings'        => $ratings,
            'waitTimes'      => FeedbackRating::WAIT_TIMES,
            'waitTimesShort' => FeedbackRating::WAIT_TIMES_SHORT,
            'criteria'       => FeedbackRating::CRITERIA,
        ]);
    }
}
