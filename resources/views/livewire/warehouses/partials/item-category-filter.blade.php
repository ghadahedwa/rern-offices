{{--
    حصر منسدلات الصنف بقسم — فوق صفوف المستند (وارد · نقل · صرف).
    يحتاج: $categories

    ⚠️ لا يمسّ الصفوف المُدخَلة: الصنف المختار يبقى في منسدلته مهما ضاق الحصر
       (يضمّه الـtrait إلى نتيجة الفلتر) — وإلا بدا الصف فارغاً وقد كان ممتلئاً.
--}}
<div class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-zinc-100 dark:border-zinc-800">
    <div class="flex flex-col gap-1 min-w-56">
        <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
            {{ __('home.wh_items_category_filter') }}
        </label>
        <select wire:model.live="itemCategoryId"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
            <option value="">{{ __('home.wh_items_category_all') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
            <option value="none">{{ __('home.item_category_none') }}</option>
        </select>
    </div>
    <p class="text-xs text-zinc-400 dark:text-zinc-500 flex-1 min-w-48 pb-2">{{ __('home.wh_items_category_hint') }}</p>
</div>
