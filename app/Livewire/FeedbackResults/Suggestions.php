<?php

namespace App\Livewire\FeedbackResults;

use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackSuggestion;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('مقترحات المواطنين')]
class Suggestions extends Component
{
    use WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** الصف المفتوح لعرض التفاصيل */
    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    /** topics_count متاح للترتيب لأن withCount يضيفه كعمود في الاستعلام. */
    protected function sortableColumns(): array
    {
        return ['created_at', 'topics_count'];
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
        $suggestions = $this->applyFilters(FeedbackSuggestion::query())
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                $norm = ArabicText::normalize($term);
                $q->where(function ($sub) use ($term, $norm) {
                    $sub->whereRaw(ArabicText::sqlNormalize('name').' LIKE ?', ["%{$norm}%"])
                        // الاقتراح الحر + عناوين الكتالوج المختارة
                        ->orWhereRaw(ArabicText::sqlNormalize('other_suggestion').' LIKE ?', ["%{$norm}%"])
                        ->orWhereHas('topics', fn ($t) => $t->whereRaw(
                            ArabicText::sqlNormalize('suggestion_topics.name').' LIKE ?', ["%{$norm}%"]
                        ))
                        ->orWhere('national_id', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->with(['office:id,name', 'governorate:id,name', 'topics.domain'])
            ->withCount('topics')
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.suggestions', [
            'suggestions' => $suggestions,
        ]);
    }
}
