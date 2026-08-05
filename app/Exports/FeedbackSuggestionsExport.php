<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * مقترحات المواطنين — ورقتان:
 *  ١) صف لكل مقترح، وعناوينه المختارة مجمّعة في خلية.
 *  ٢) صف لكل (مقترح × عنوان) — الشكل الوحيد الذي يصلح لجدول محوري في Excel.
 *
 * ⚠️ الاستعلام يأتي جاهزاً من الشاشة (SuggestionsQuery) ولا يُعاد بناؤه هنا،
 * وإلا خرج الملف بصفوف تخالف ما يراه المستخدم.
 */
class FeedbackSuggestionsExport implements WithMultipleSheets
{
    public function __construct(
        protected Builder $query,
        protected bool $includePersonal = false,
    ) {}

    public function sheets(): array
    {
        return [
            new FeedbackSuggestionsSheet(clone $this->query, $this->includePersonal),
            new FeedbackSuggestionTopicsSheet(clone $this->query),
        ];
    }
}
