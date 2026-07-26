{{-- قسم الهوية والمقر — مشترك بين فورم التقييم والمقترحات --}}
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
             x-data x-on:input="$el.value = digitsOnly($el.value)" placeholder="١٤ رقماً" />
      @error('national_id')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
      <label>رقم الهاتف المحمول <span class="req">*</span></label>
      <input type="tel" inputmode="numeric" maxlength="11" class="inp" wire:model="phone"
             x-data x-on:input="$el.value = digitsOnly($el.value)" placeholder="01XXXXXXXXX" />
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
