<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // All authenticated users can view governorates list
    Route::livewire('governorates', \App\Livewire\Governorates\Index::class)->name('governorates.index');

    Route::middleware('role:super-admin')->group(function () {
        Route::livewire('users', \App\Livewire\Users\Index::class)->name('users.index');
        Route::livewire('users/create', \App\Livewire\Users\Create::class)->name('users.create');
        Route::livewire('users/{user}/edit', \App\Livewire\Users\Edit::class)->name('users.edit');

        Route::livewire('roles', \App\Livewire\Roles\Index::class)->name('roles.index');
        Route::livewire('roles/create', \App\Livewire\Roles\Create::class)->name('roles.create');
        Route::livewire('roles/{role}/edit', \App\Livewire\Roles\Edit::class)->name('roles.edit');

        Route::livewire('governorates/create', \App\Livewire\Governorates\Create::class)->name('governorates.create');
        Route::livewire('governorates/{governorate}/edit', \App\Livewire\Governorates\Edit::class)->name('governorates.edit');
    });
});

require __DIR__.'/settings.php';
