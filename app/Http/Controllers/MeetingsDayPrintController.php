<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingsDayPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(auth()->user()?->can('meetings.index'), 403);

        $date = $request->query('date') ?: now()->toDateString();

        $meetings = Meeting::whereDate('date', $date)
            ->orderBy('time')
            ->get();

        return view('print.meetings-day', [
            'date'     => $date,
            'meetings' => $meetings,
        ]);
    }
}
