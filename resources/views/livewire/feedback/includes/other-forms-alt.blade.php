{{-- روابط الفورمين الآخرين في شاشة الحجب — مشتركة بين الفورمات الثلاثة (otherForms() في الـtrait) --}}
<div class="alts">
  @foreach($this->otherForms() as $form)
    <a href="{{ $form['url'] }}" wire:navigate class="alt">
      {{ $form['alt'] }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
  @endforeach
</div>
