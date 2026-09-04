{{--
    خيارات الصنف في منسدلات الإدخال — **مجمَّعة بالقسم**.

    يحتاج: $items (بترتيب الدفتر، محمَّلاً عليها category)
    واختياري: $chosen · $line — فورمات الإدخال تمرّرهما لإخفاء المُختار في صفٍّ
    آخر، ومنسدلات الفلترة لا تمرّرهما فتعرض الأصناف كلها.

    ⚠️ بُنيت شاشات الإدخال قبل وجود أقسام الأصناف، فكانت تعرض **٣٧٧ صنفاً في
       قائمة مسطّحة بالاسم وحده**. والموظف يعرف الصنف بقسمه («المستديم»،
       «الدفتر العقاري») وبرقمه في الدفتر — فبلا الاثنين يبحث بعينه في قائمة
       لا رأس لها.

    ⚠️ والتجميع يحافظ على **ترتيب الدفتر**: `Item::statementOrder` يرتّب بالقسم
       أولاً، و`groupBy` يبقي المجموعات بترتيب أول ظهور — فلا يُعاد ترتيبها
       أبجدياً، والأصناف بلا قسم تقع آخراً كما في الدفتر.

    ⚠️ ويبقى شرط الإخفاء كما كان: الصنف المختار في صفٍّ آخر يُخفى من هذا الصف
       ويبقى ظاهراً في صفّه هو — وإلا اختفى من منسدلته فبدا الصف فارغاً.
--}}
@php
    $chosenItems = $chosen ?? [];
    $currentLine = $line ?? [];
    $itemGroups  = $items->groupBy(fn ($item) => $item->category?->name ?? '');
@endphp

@foreach($itemGroups as $categoryName => $groupItems)
    @php
        $visibleItems = $groupItems->filter(
            fn ($item) => ! in_array($item->id, $chosenItems, true)
                || (int) ($currentLine['item_id'] ?? 0) === $item->id
        );
    @endphp

    {{-- قسمٌ خلت أصنافه (كلها مُختارة في صفوف أخرى) لا يُعرض عنواناً فارغاً --}}
    @if($visibleItems->isNotEmpty())
        <optgroup label="{{ $categoryName !== '' ? $categoryName : __('home.item_category_none') }}">
            @foreach($visibleItems as $item)
                {{-- ⚠️ الاسم يُركَّب في PHP لا بـ`@if` داخل الوسم: Livewire يحقن
                     تعليقات `<!--[if BLOCK]-->` حول كل شرط، فتقع **داخل نصّ
                     الخيار** فتقطّعه — «الاسم<!--…--> — الرقم<!--…-->». --}}
                @php $itemLabel = $item->code ? $item->name.' — '.$item->code : $item->name; @endphp
                {{-- ⚠️ `selected` صريحة: خيارات المنسدلة تُبدَّل كلها حين يتغيّر قسمُ
                     الصفّ، والمتصفح لا يعرف المختار إلا من الوسم نفسه — فبلا هذا
                     تبدو المنسدلة فارغة والقيمة محفوظة، فيمضي الحفظ بصنفٍ لا يُرى. --}}
                <option value="{{ $item->id }}" @selected((int) ($currentLine['item_id'] ?? 0) === $item->id)>{{ $itemLabel }}</option>
            @endforeach
        </optgroup>
    @endif
@endforeach
