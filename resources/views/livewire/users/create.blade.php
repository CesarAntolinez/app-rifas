<?php

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
]);

rules([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'unique:users,email'],
    'password' => ['required', 'confirmed', new StrongPassword()],
]);

$save = function (NotificationService $notificationService) {
    $this->authorize('create', User::class);
    $this->validate();

    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'password' => Hash::make($this->password, ['rounds' => 12]),
        'role' => 'organizer',
        'must_change_password' => true,
        'is_active' => true,
    ]);

    $notificationService->notifyOrganizerWelcome($user, $this->password);

    session()->flash('success', 'Organizador creado exitosamente.');
    $this->redirectRoute('users.index', navigate: true);
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Nuevo organizador</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('Nombre')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Contraseña temporal')" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="text-xs text-gray-500">
                        <p>La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('users.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</a>
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Crear organizador</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
