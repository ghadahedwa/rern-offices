{{-- قسم الهوية والمقر — مشترك بين فورم التقييم والمقترحات --}}

{{-- honeypot: حقل مصيدة مخفي — المواطن لا يراه، والبوت يملؤه فيُكشف.
     نستخدم نمط visually-hidden (لا يزيح خارج الشاشة) لتجنّب سكرول أفقي في RTL. --}}
<div aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">
  <label>Website</label>
  <input type="text" tabindex="-1" autocomplete="off" wire:model="website" />
</div>

<div class="sec">
  <div class="sec-head"><span class="bar"></span><h2>بياناتك وتحديد المقر</h2></div>

  <div class="field">
    <label>الاسم <span class="req">*</span></label>
    <input type="text" class="inp" wire:model="name" placeholder="الاسم بالكامل" />
    @error('name')<p class="err">{{ $message }}</p>@enderror
  </div>

  <div class="grid2">
    <div class="field">
      <label>الرقم القومي <span class="req">*</span></label>
      <input type="text" inputmode="numeric" maxlength="14" class="inp" wire:model.blur="national_id"
             oninput="this.value = digitsOnly(this.value)" placeholder="١٤ رقماً" />
      @error('national_id')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
      <label>رقم الهاتف المحمول <span class="req">*</span></label>
      <input type="tel" inputmode="numeric" maxlength="11" class="inp" wire:model.blur="phone"
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
    <div class="field">
      <label>المقر <span class="req">*</span></label>
      <select class="inp" wire:model.live="office_id" @disabled(! $governorate_id)>
        <option value="">{{ $governorate_id ? 'اختر المقر' : 'اختر المحافظة أولاً' }}</option>
        @foreach($this->offices as $office)
          <option value="{{ $office->id }}">{{ $office->name }}</option>
        @endforeach
      </select>
      @error('office_id')<p class="err">{{ $message }}</p>@enderror
    </div>
  </div>
</div>
