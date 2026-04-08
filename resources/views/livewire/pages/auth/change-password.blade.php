<?php

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.guest');

state([
    'password' => '',
    'password_confirmation' => '',
]);

rules([
    'password' => ['required', 'confirmed', new StrongPassword()],
]);

$changePassword = function () {
    $this->validate();

    $user = Auth::user();

    $user->update([
        'password' => Hash::make($this->password, ['rounds' => 12]),
        'must_change_password' => false,
    ]);

    $this->redirectRoute('dashboard', navigate: true);
};

?>

<div>
    <div class="mb-6 text-sm text-gray-600">
        {{ __('Debes cambiar tu contraseña antes de continuar.') }}
    </div>

    <form wire:submit="changePassword">
        <div>
            <x-input-label for="password" :value="__('Nueva contraseña')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4 text-xs text-gray-500">
            <p>La contraseña debe contener:</p>
            <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Al menos 8 caracteres</li>
                <li>Al menos una letra mayúscula</li>
                <li>Al menos un número</li>
                <li>Al menos un carácter especial</li>
            </ul>
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button wire:loading.attr="disabled" wire:target="changePassword">
                <span wire:loading.remove wire:target="changePassword">{{ __('Actualizar contraseña') }}</span>
                <span wire:loading wire:target="changePassword">{{ __('Guardando...') }}</span>
            </x-primary-button>
        </div>
    </form>
</div>
