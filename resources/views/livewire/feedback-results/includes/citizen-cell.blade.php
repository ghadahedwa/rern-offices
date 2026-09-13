{{-- خلية هوية المواطن — مشتركة بين شاشتي التقييمات والمقترحات.
     الحقول الثلاثة اختيارية؛ «مجهول» = بلا رقم قومي وبلا هاتف (تعريفه الواحد في HasFeedbackIdentity).
     مَن كتب اسمه وحده يُعرض اسمه ومعه الشارة — الاسم لا يُعرّف صاحبه. --}}
@if($row->name)
    <span class="block font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $row->name }}">{{ $row->name }}</span>
@endif

@if($row->isAnonymous())
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 {{ $row->name ? 'mt-1' : '' }}">
        {{ __('home.fr_anonymous') }}
    </span>
@else
    @if($row->national_id)
        <span class="block text-xs text-zinc-400">{{ $row->national_id }}</span>
    @endif
    @if($row->phone)
        <span class="block text-xs text-zinc-400">{{ $row->phone }}</span>
    @endif
@endif
