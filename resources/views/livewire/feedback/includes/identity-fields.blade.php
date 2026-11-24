{{-- قسم الهوية والمقر — مشترك بين فورمات البوابة.
     $identityRequired: الهوية إلزامية لبعض الرسائل (شكر موظف)، واختيارية في الباقي. --}}
@php($identityRequired = $identityRequired ?? false)

{{-- honeypot: حقل مصيدة مخفي — المواطن لا يراه، والبوت يملؤه فيُكشف.
     نستخدم نمط visually-hidden (لا يزيح خارج الشاشة) لتجنّب سكرول أفقي في RTL. --}}
<div aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">
  <label>Website</label>
  <input type="text" tabindex="-1" autocomplete="off" wire:model="website" />
</div>

<div class="sec">
  <div class="sec-head"><span class="bar"></span><h2>بياناتك وتحديد المقر</h2></div>

  {{-- الهوية اختيارية (2026-09-14) — منع التكرار ببصمة الجهاز لا بالهوية. انظر FeedbackDevice --}}
  @if($identityRequired)
    <p class="hint">البيانات الشخصية <strong>مطلوبة</strong> هنا للتحقق من الرسالة والتواصل معك.</p>
  @else
    <p class="hint">البيانات الشخصية إدخالها <strong>اختياري</strong> — ويُفضَّل إدخالها للتواصل.</p>
  @endif

  <div class="field">
    <label>الاسم @if($identityRequired)<span class="req">*</span>@else<span class="opt">(اختياري)</span>@endif</label>
    <input type="text" class="inp" wire:model="name" placeholder="الاسم بالكامل" />
    @error('name')<p class="err">{{ $message }}</p>@enderror
  </div>

  <div class="grid2">
    <div class="field">
      <label>الرقم القومي @if($identityRequired)<span class="req">*</span>@else<span class="opt">(اختياري)</span>@endif</label>
      <input type="text" inputmode="numeric" maxlength="14" class="inp" wire:model.live.debounce.600ms="national_id"
             oninput="this.value = digitsOnly(this.value)" placeholder="١٤ رقماً" />
      @error('national_id')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
      <label>رقم الهاتف المحمول @if($identityRequired)<span class="req">*</span>@else<span class="opt">(اختياري)</span>@endif</label>
      <input type="tel" inputmode="numeric" maxlength="11" class="inp" wire:model.live.debounce.600ms="phone"
             oninput="this.value = digitsOnly(this.value)" placeholder="01XXXXXXXXX" />
      @error('phone')<p class="err">{{ $message }}</p>@enderror
    </div>
  </div>

  <div class="grid2">
    <div class="field">
      <label>المحافظة <span class="req">*</span></label>
      <select class="inp" wire:model.live="governorate_id">
        <option value="">اختر المحافظة</option>
        @foreach($this->governorates as $gov)
          <option value="{{ $gov->id }}">{{ $gov->name }}</option>
        @endforeach
      </select>
      @error('governorate_id')<p class="err">{{ $message }}</p>@enderror
    </div>
    {{-- المقر: منسدلة ببحثٍ داخلها، داخل مقرات المحافظة المختارة وحدها (الأسماء تتكرر بين المحافظات).
         البحث في السيرفر بـArabicText (officeSearch)، والمقر المختار يبقى في القائمة ولو لم يطابق. --}}
    @php($chosenOffice = $office_id ? $this->offices->firstWhere('id', (int) $office_id) : null)
    <div class="field combo" wire:key="office-combo"
         x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
      <label>المقر <span class="req">*</span></label>
      <button type="button" class="inp combo-btn" @disabled(! $governorate_id)
              @click="open = ! open; if (open) $nextTick(() => $refs.q.focus())">
        <span class="{{ $chosenOffice ? '' : 'ph' }}">{{ $chosenOffice?->name ?? ($governorate_id ? 'اختر المقر' : 'اختر المحافظة أولاً') }}</span>
      </button>
      <div class="combo-panel" x-show="open" x-cloak>
        <input x-ref="q" type="text" class="inp combo-search" wire:model.live.debounce.300ms="officeSearch"
               placeholder="ابحث باسم المقر..." autocomplete="off" />
        <ul class="combo-list">
          @forelse($this->offices as $office)
            <li wire:key="office-opt-{{ $office->id }}">
              <button type="button" class="{{ (int) $office_id === $office->id ? 'sel' : '' }}"
                      wire:click="$set('office_id', {{ $office->id }})" @click="open = false">{{ $office->name }}</button>
            </li>
          @empty
            <li class="combo-empty">لا توجد مقرات بهذا الاسم في المحافظة المختارة.</li>
          @endforelse
        </ul>
      </div>
      @error('office_id')<p class="err">{{ $message }}</p>@enderror
    </div>
  </div>
</div>
