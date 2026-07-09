<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    //Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    // Governorates — access controlled per-permission inside component mount()
    Route::livewire('governorates', \App\Livewire\Governorates\Index::class)->name('governorates.index');
    Route::livewire('governorates/create', \App\Livewire\Governorates\Create::class)->name('governorates.create');
    Route::livewire('governorates/{governorate}/edit', \App\Livewire\Governorates\Create::class)->name('governorates.edit');

    // المطالبات على مستوى المحافظة — متاح لكل المستخدمين حالياً (مفلتر بمحافظات اليوزر داخل المكوّن)
    Route::livewire('claims', \App\Livewire\Claims\Index::class)->name('claims.index');

    // Offices — access controlled per-permission inside component mount()
    Route::livewire('offices', \App\Livewire\Offices\Index::class)->name('offices.index');
    Route::livewire('offices/create', \App\Livewire\Offices\Create::class)->name('offices.create');
    Route::livewire('offices/{office}', \App\Livewire\Offices\Show::class)->name('offices.show');
    Route::livewire('offices/{office}/edit', \App\Livewire\Offices\Create::class)->name('offices.edit');
    Route::livewire('offices/{office}/statistics', \App\Livewire\Offices\Statistics::class)->name('offices.statistics');

    // Vehicles — access controlled per-permission inside component mount()
    Route::livewire('vehicles', \App\Livewire\Vehicles\Index::class)->name('vehicles.index');
    Route::livewire('vehicles/create', \App\Livewire\Vehicles\Create::class)->name('vehicles.create');
    Route::livewire('vehicles/{vehicle}', \App\Livewire\Vehicles\Show::class)->name('vehicles.show');
    Route::livewire('vehicles/{vehicle}/edit', \App\Livewire\Vehicles\Create::class)->name('vehicles.edit');

    // دليل الهاتف للمقرات — صلاحية offices.phone-directory (تُفحص داخل mount)
    Route::livewire('offices-phone-directory', \App\Livewire\Reports\PhoneDirectory::class)->name('offices.phone-directory');

    Route::livewire('reports/office-pdf', \App\Livewire\Reports\OfficePdf::class)->name('reports.office-pdf');
    Route::livewire('reports/multi-office', \App\Livewire\Reports\MultiOffice::class)->name('reports.multi-office');
    Route::get('reports/multi-office/pdf', \App\Http\Controllers\OfficesReportPdfController::class)->name('reports.multi-office.pdf');
    Route::get('reports/multi-office/custom-pdf', \App\Http\Controllers\MultiOfficeCustomPdfController::class)->name('reports.multi-office.custom-pdf');
    Route::livewire('reports/offices-by-type', \App\Livewire\Reports\OfficesByType::class)->name('reports.offices-by-type');
    Route::get('reports/offices-by-type/pdf', \App\Http\Controllers\OfficesByTypePdfController::class)->name('reports.offices-by-type.pdf');
    Route::livewire('reports/devices', \App\Livewire\Reports\DeviceCount::class)->name('reports.device-count');
    Route::get('reports/devices/pdf', \App\Http\Controllers\DeviceCountPdfController::class)->name('reports.device-count.pdf');
    Route::livewire('reports/stats-comparison', \App\Livewire\Reports\StatsComparison::class)->name('reports.stats-comparison');
    Route::livewire('reports/multi-vehicle', \App\Livewire\Reports\MultiVehicle::class)->name('reports.multi-vehicle');
    Route::get('reports/multi-vehicle/pdf', \App\Http\Controllers\VehiclesReportPdfController::class)->name('reports.multi-vehicle.pdf');
    Route::get('reports/multi-vehicle/custom-pdf', \App\Http\Controllers\MultiVehicleCustomPdfController::class)->name('reports.multi-vehicle.custom-pdf');
    Route::livewire('reports/vehicle-devices', \App\Livewire\Reports\VehicleDeviceCount::class)->name('reports.vehicle-device-count');
    Route::get('reports/vehicle-devices/pdf', \App\Http\Controllers\VehicleDeviceCountPdfController::class)->name('reports.vehicle-device-count.pdf');
    Route::livewire('reports/vehicle-status', \App\Livewire\Reports\VehicleStatus::class)->name('reports.vehicle-status');
    Route::get('reports/vehicle-status/pdf', \App\Http\Controllers\VehicleStatusPdfController::class)->name('reports.vehicle-status.pdf');
    Route::livewire('reports/vehicle-coverage', \App\Livewire\Reports\VehicleCoverage::class)->name('reports.vehicle-coverage');
    Route::get('reports/vehicle-coverage/pdf', \App\Http\Controllers\VehicleCoveragePdfController::class)->name('reports.vehicle-coverage.pdf');
    Route::livewire('reports/vehicle-licenses', \App\Livewire\Reports\VehicleLicenses::class)->name('reports.vehicle-licenses');
    Route::get('reports/vehicle-licenses/pdf', \App\Http\Controllers\VehicleLicensesPdfController::class)->name('reports.vehicle-licenses.pdf');
    Route::livewire('reports/claims-statement', \App\Livewire\Reports\ClaimsStatement::class)->name('reports.claims-statement');
    Route::get('reports/claims-statement/pdf', \App\Http\Controllers\ClaimsStatementPdfController::class)->name('reports.claims-statement.pdf');
    Route::livewire('reports/claims-summary', \App\Livewire\Reports\ClaimsSummary::class)->name('reports.claims-summary');
    Route::get('reports/claims-summary/pdf', \App\Http\Controllers\ClaimsSummaryPdfController::class)->name('reports.claims-summary.pdf');
    Route::get('offices/{office}/pdf', [\App\Http\Controllers\OfficePdfController::class, '__invoke'])->name('offices.pdf');

    Route::middleware('role:super-admin')->group(function () {
        Route::livewire('users', \App\Livewire\Users\Index::class)->name('users.index');
        Route::livewire('users/create', \App\Livewire\Users\Create::class)->name('users.create');
        Route::livewire('users/{user}/edit', \App\Livewire\Users\Edit::class)->name('users.edit');

        Route::livewire('roles', \App\Livewire\Roles\Index::class)->name('roles.index');
        Route::livewire('roles/create', \App\Livewire\Roles\Create::class)->name('roles.create');
        Route::livewire('roles/{role}/edit', \App\Livewire\Roles\Edit::class)->name('roles.edit');

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

        Route::livewire('disabilities-access', \App\Livewire\DisabilitiesAccess\Index::class)->name('disabilities-access.index');
        Route::livewire('disabilities-access/create', \App\Livewire\DisabilitiesAccess\Create::class)->name('disabilities-access.create');
        Route::livewire('disabilities-access/{disabilitiesAccess}/edit', \App\Livewire\DisabilitiesAccess\Create::class)->name('disabilities-access.edit');

        Route::livewire('fire-safety', \App\Livewire\FireSafety\Index::class)->name('fire-safety.index');
        Route::livewire('fire-safety/create', \App\Livewire\FireSafety\Create::class)->name('fire-safety.create');
        Route::livewire('fire-safety/{fireSafety}/edit', \App\Livewire\FireSafety\Create::class)->name('fire-safety.edit');

        Route::livewire('document-photocopying-services', \App\Livewire\DocumentPhotocopyingServices\Index::class)->name('document-photocopying-services.index');
        Route::livewire('document-photocopying-services/create', \App\Livewire\DocumentPhotocopyingServices\Create::class)->name('document-photocopying-services.create');
        Route::livewire('document-photocopying-services/{documentPhotocopyingService}/edit', \App\Livewire\DocumentPhotocopyingServices\Create::class)->name('document-photocopying-services.edit');

        Route::livewire('buffet-services', \App\Livewire\BuffetServices\Index::class)->name('buffet-services.index');
        Route::livewire('buffet-services/create', \App\Livewire\BuffetServices\Create::class)->name('buffet-services.create');
        Route::livewire('buffet-services/{buffetService}/edit', \App\Livewire\BuffetServices\Create::class)->name('buffet-services.edit');

        Route::livewire('cleanliness-contracts', \App\Livewire\CleanlinessContracts\Index::class)->name('cleanliness-contracts.index');
        Route::livewire('cleanliness-contracts/create', \App\Livewire\CleanlinessContracts\Create::class)->name('cleanliness-contracts.create');
        Route::livewire('cleanliness-contracts/{cleanlinessContract}/edit', \App\Livewire\CleanlinessContracts\Create::class)->name('cleanliness-contracts.edit');

        Route::livewire('microfilm-options', \App\Livewire\MicrofilmOptions\Index::class)->name('microfilm-options.index');
        Route::livewire('microfilm-options/create', \App\Livewire\MicrofilmOptions\Create::class)->name('microfilm-options.create');
        Route::livewire('microfilm-options/{microfilmOption}/edit', \App\Livewire\MicrofilmOptions\Create::class)->name('microfilm-options.edit');

        Route::livewire('vehicle-types', \App\Livewire\VehicleTypes\Index::class)->name('vehicle-types.index');
        Route::livewire('vehicle-types/create', \App\Livewire\VehicleTypes\Create::class)->name('vehicle-types.create');
        Route::livewire('vehicle-types/{vehicleType}/edit', \App\Livewire\VehicleTypes\Create::class)->name('vehicle-types.edit');

        Route::livewire('vehicle-brands', \App\Livewire\VehicleBrands\Index::class)->name('vehicle-brands.index');
        Route::livewire('vehicle-brands/create', \App\Livewire\VehicleBrands\Create::class)->name('vehicle-brands.create');
        Route::livewire('vehicle-brands/{vehicleBrand}/edit', \App\Livewire\VehicleBrands\Create::class)->name('vehicle-brands.edit');

        Route::livewire('vehicle-work-systems', \App\Livewire\VehicleWorkSystems\Index::class)->name('vehicle-work-systems.index');
        Route::livewire('vehicle-work-systems/create', \App\Livewire\VehicleWorkSystems\Create::class)->name('vehicle-work-systems.create');
        Route::livewire('vehicle-work-systems/{vehicleWorkSystem}/edit', \App\Livewire\VehicleWorkSystems\Create::class)->name('vehicle-work-systems.edit');

        Route::livewire('vehicle-working-hours', \App\Livewire\VehicleWorkingHours\Index::class)->name('vehicle-working-hours.index');
        Route::livewire('vehicle-working-hours/create', \App\Livewire\VehicleWorkingHours\Create::class)->name('vehicle-working-hours.create');
        Route::livewire('vehicle-working-hours/{vehicleWorkingHour}/edit', \App\Livewire\VehicleWorkingHours\Create::class)->name('vehicle-working-hours.edit');

        Route::livewire('vehicle-device-types', \App\Livewire\VehicleDeviceTypes\Index::class)->name('vehicle-device-types.index');
        Route::livewire('vehicle-device-types/create', \App\Livewire\VehicleDeviceTypes\Create::class)->name('vehicle-device-types.create');
        Route::livewire('vehicle-device-types/{vehicleDeviceType}/edit', \App\Livewire\VehicleDeviceTypes\Create::class)->name('vehicle-device-types.edit');
    });
});

require __DIR__.'/settings.php';
