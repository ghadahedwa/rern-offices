<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // All authenticated users can view governorates list
    Route::livewire('governorates', \App\Livewire\Governorates\Index::class)->name('governorates.index');

    // Offices — access controlled per-permission inside component mount()
    Route::livewire('offices', \App\Livewire\Offices\Index::class)->name('offices.index');
    Route::livewire('offices/create', \App\Livewire\Offices\Create::class)->name('offices.create');
    Route::livewire('offices/{office}/edit', \App\Livewire\Offices\Create::class)->name('offices.edit');
    Route::livewire('offices/{office}/statistics', \App\Livewire\Offices\Statistics::class)->name('offices.statistics');

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
    });
});

require __DIR__.'/settings.php';
