{{-- منسدلة المحافظات — `multi-picker` بنصوص المحافظة، في اللوحة وتقريري المحافظات والمقر.
     $selected · $choices · $required · $compact — انظر `multi-picker`. --}}
@include('livewire.contractors.reports.includes.multi-picker', [
    'model'       => 'governorateIds',
    'searchModel' => 'governorateSearch',
    'selected'    => $selected,
    'choices'     => $choices,
    'required'    => $required ?? false,
    'compact'     => $compact ?? false,
    'layout'      => 'grid',
    'label'       => __('home.ct_rep_governorate'),
    'allText'     => __('home.ct_rep_all_governorates'),
    'pickText'    => __('home.ct_rep_pick_governorates'),
    'pickedKey'   => 'home.ct_dash_picked_governorates',
    'searchText'  => __('home.ct_rep_search_governorate'),
])
