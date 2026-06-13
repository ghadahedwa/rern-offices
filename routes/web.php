<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    //Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    // All authenticated users can view governorates list
    Route::livewire('governorates', \App\Livewire\Governorates\Index::class)->name('governorates.index');

    // Offices — access controlled per-permission inside component mount()
    Route::livewire('offices', \App\Livewire\Offices\Index::class)->name('offices.index');
    Route::livewire('offices/create', \App\Livewire\Offices\Create::class)->name('offices.create');
    Route::livewire('offices/{office}', \App\Livewire\Offices\Show::class)->name('offices.show');
    Route::livewire('offices/{office}/edit', \App\Livewire\Offices\Create::class)->name('offices.edit');
    Route::livewire('offices/{office}/statistics', \App\Livewire\Offices\Statistics::class)->name('offices.statistics');

    Route::livewire('reports/office-pdf', \App\Livewire\Reports\OfficePdf::class)->name('reports.office-pdf');
    Route::livewire('reports/multi-office', \App\Livewire\Reports\MultiOffice::class)->name('reports.multi-office');
    Route::get('reports/multi-office/pdf', \App\Http\Controllers\OfficesReportPdfController::class)->name('reports.multi-office.pdf');
    Route::get('offices/{office}/pdf', [\App\Http\Controllers\OfficePdfController::class, '__invoke'])->name('offices.pdf');

    Route::middleware('role:super-admin')->group(function () {
        Route::livewire('users', \App\Livewire\Users\Index::class)->name('users.index');
        Route::livewire('users/create', \App\Livewire\Users\Create::class)->name('users.create');
        Route::livewire('users/{user}/edit', \App\Livewire\Users\Edit::class)->name('users.edit');

        Route::livewire('roles', \App\Livewire\Roles\Index::class)->name('roles.index');
        Route::livewire('roles/create', \App\Livewire\Roles\Create::class)->name('roles.create');
        Route::livewire('roles/{role}/edit', \App\Livewire\Roles\Edit::class)->name('roles.edit');

        Route::livewire('governorates/create', \App\Livewire\Governorates\Create::class)->name('governorates.create');
        Route::livewire('governorates/{governorate}/edit', \App\Livewire\Governorates\Create::class)->name('governorates.edit');

        Route::livewire('office-types', \App\Livewire\OfficeTypes\Index::class)->name('office-types.index');
        Route::livewire('office-types/create', \App\Livewire\OfficeTypes\Create::class)->name('office-types.create');
        Route::livewire('office-types/{officeType}/edit', \App\Livewire\OfficeTypes\Create::class)->name('office-types.edit');

        Route::livewire('location-descriptions', \App\Livewire\LocationDescriptions\Index::class)->name('location-descriptions.index');
        Route::livewire('location-descriptions/create', \App\Livewire\LocationDescriptions\Create::class)->name('location-descriptions.create');
        Route::livewire('location-descriptions/{locationDescription}/edit', \App\Livewire\LocationDescriptions\Create::class)->name('location-descriptions.edit');

        Route::livewire('work-systems', \App\Livewire\WorkSystems\Index::class)->name('work-systems.index');
        Route::livewire('work-systems/create', \App\Livewire\WorkSystems\Create::class)->name('work-systems.create');
        Route::livewire('work-systems/{workSystem}/edit', \App\Livewire\WorkSystems\Create::class)->name('work-systems.edit');

        Route::livewire('working-hours', \App\Livewire\WorkingHours\Index::class)->name('working-hours.index');
        Route::livewire('working-hours/create', \App\Livewire\WorkingHours\Create::class)->name('working-hours.create');
        Route::livewire('working-hours/{workingHour}/edit', \App\Livewire\WorkingHours\Create::class)->name('working-hours.edit');

        Route::livewire('connection-types', \App\Livewire\ConnectionTypes\Index::class)->name('connection-types.index');
        Route::livewire('connection-types/create', \App\Livewire\ConnectionTypes\Create::class)->name('connection-types.create');
        Route::livewire('connection-types/{connectionType}/edit', \App\Livewire\ConnectionTypes\Create::class)->name('connection-types.edit');

        Route::livewire('device-types', \App\Livewire\DeviceTypes\Index::class)->name('device-types.index');
        Route::livewire('device-types/create', \App\Livewire\DeviceTypes\Create::class)->name('device-types.create');
        Route::livewire('device-types/{deviceType}/edit', \App\Livewire\DeviceTypes\Create::class)->name('device-types.edit');

        Route::livewire('contractual-statuses', \App\Livewire\ContractualStatuses\Index::class)->name('contractual-statuses.index');
        Route::livewire('contractual-statuses/create', \App\Livewire\ContractualStatuses\Create::class)->name('contractual-statuses.create');
        Route::livewire('contractual-statuses/{contractualStatus}/edit', \App\Livewire\ContractualStatuses\Create::class)->name('contractual-statuses.edit');

        Route::livewire('structural-conditions', \App\Livewire\StructuralConditions\Index::class)->name('structural-conditions.index');
        Route::livewire('structural-conditions/create', \App\Livewire\StructuralConditions\Create::class)->name('structural-conditions.create');
        Route::livewire('structural-conditions/{structuralCondition}/edit', \App\Livewire\StructuralConditions\Create::class)->name('structural-conditions.edit');
    });
});

require __DIR__.'/settings.php';
