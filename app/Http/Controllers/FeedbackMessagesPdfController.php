<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersFeedbackPdf;
use App\Models\FeedbackMessage;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\FeedbackSort;
use App\Support\FeedbackResults\MessagesQuery;
use Illuminate\Http\Request;

/**
 * رسائل نوعٍ بعينه كتقرير مطبوع. بيانات المواطن بـ ?personal=1 وحدها.
 *
 * ⚠️ حارسان: `feedback.export` (التقرير تصدير) **و** صلاحية النوع نفسه —
 *    وإلا طبع صاحبُ التصدير بلاغاتٍ مقصورة على السوبر أدمن برابطٍ منسوخ.
 */
class FeedbackMessagesPdfController extends Controller
{
    use RendersFeedbackPdf;

    public function __invoke(Request $request, string $messageType)
    {
        $this->guardPdf($request);
        abort_unless(FeedbackAccess::canViewMessages($request->user(), $messageType), 403);

        $filters  = FeedbackFilterSet::fromRequest($request);
        $personal = $request->boolean('personal');
        $trashed  = $this->viewingTrash($request);

        $query = MessagesQuery::build($messageType, $filters, $request->user(), $this->param($request, 'q'), $trashed);

        $total = (clone $query)->count();

        $messages = FeedbackSort::apply(
            $query->with(['office:id,name', 'governorate:id,name']),
            $this->param($request, 'sort'),
            $this->param($request, 'dir'),
            MessagesQuery::SORTABLE,
        )->limit(self::MAX_ROWS)->get();

        return $this->renderPdf('print.feedback-messages-pdf', [
            'messages'    => $messages,
            'type'        => $messageType,
            'config'      => FeedbackMessage::config($messageType),
            'total'       => $total,
            'maxRows'     => self::MAX_ROWS,
            'personal'    => $personal,
            'trashed'     => $trashed,
            'filters'     => $filters,
            'generatedAt' => now(),
        ], 'feedback-'.$messageType, landscape: true);
    }
}
