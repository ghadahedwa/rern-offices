{{-- الرأي المكتوب كاملاً (لا truncate: هو مضمون التقرير)، وعناوين المقترح المختارة تحته --}}
@if(filled($row->opinion))
    <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line break-words">{{ $row->opinion }}</p>
@else
    <p class="text-xs text-zinc-400">{{ __('home.fr_no_text') }}</p>
@endif

@if($rowTopics !== '')
    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400"><span class="font-medium">{{ __('home.fr_topics') }}:</span> {{ $rowTopics }}</p>
@endif
