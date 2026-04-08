<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth', 'check.active', 'force.password.change'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // Users (Admin only)
    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('users/create', 'users.create')->name('users.create');

    // Raffles
    Volt::route('raffles', 'raffles.index')->name('raffles.index');
    Volt::route('raffles/create', 'raffles.create')->name('raffles.create');
    Volt::route('raffles/{raffle}', 'raffles.show')->name('raffles.show');
    Volt::route('raffles/{raffle}/edit', 'raffles.edit')->name('raffles.edit');
    Volt::route('raffles/{raffle}/execute', 'raffles.execute')->name('raffles.execute');
    Volt::route('raffles/{raffle}/results', 'raffles.results')->name('raffles.results');
    Volt::route('raffles/{raffle}/audit', 'raffles.audit')->name('raffles.audit');

    // Participants
    Volt::route('raffles/{raffle}/participants', 'participants.index')->name('participants.index');
    Volt::route('raffles/{raffle}/participants/create', 'participants.create')->name('participants.create');
    Volt::route('raffles/{raffle}/participants/import', 'participants.import')->name('participants.import');

    // Analytics
    Volt::route('analytics', 'analytics.dashboard')->name('analytics.dashboard');
});

// Password change (forced — excludes force.password.change middleware to avoid redirect loop)
Route::middleware(['auth', 'check.active'])->group(function () {
    Volt::route('password/change', 'pages.auth.change-password')->name('password.change');
});

require __DIR__.'/auth.php';
