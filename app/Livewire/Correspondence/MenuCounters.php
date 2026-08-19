<?php

namespace App\Livewire\Correspondence;

use App\Support\CorrespondenceCounters;
use Livewire\Component;

/**
 * بنود منيو المراسلات وعدّاداتها.
 *
 * مكوّن Livewire لا blade — لأنه الموضع الوحيد الذي يحتاج `wire:poll`:
 * أكثر ما يُجلَس فيه ساكناً هو الوارد انتظاراً لشيء يصل. وخارج الفرع
 * لا يُرسَم هذا المكوّن أصلاً، فلا طلب ولا حمل.
 *
 * ⚠️ `wire:poll.300s` بلا `keep-alive` — Livewire يوقف الـpoll للتاب في الخلفية،
 *    وهو ما نريده: التحديث وقت النظر فقط.
 */
class MenuCounters extends Component
{
    public function render()
    {
        $counters = app(CorrespondenceCounters::class);

        return view('livewire.correspondence.menu-counters', [
            'inboxCount'       => $counters->inbox(),
            'draftsCount'      => $counters->drafts(),
            'assignmentsCount' => $counters->assignments(),
        ]);
    }
}
