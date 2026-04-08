<?php

use App\Models\User;
use App\Services\NotificationService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'users' => [],
    'confirmingToggle' => null,
]);

mount(function () {
    $this->authorize('create', User::class);
    $this->users = User::where('role', 'organizer')->orderBy('created_at', 'desc')->get();
});

$toggleStatus = function (int $userId) {
    $user = User::findOrFail($userId);
    $this->authorize('toggleStatus', $user);

    $user->update(['is_active' => ! $user->is_active]);
    $this->confirmingToggle = null;
    $this->users = User::where('role', 'organizer')->orderBy('created_at', 'desc')->get();
};

$confirmToggle = function (int $userId) {
    $this->confirmingToggle = $userId;
};

$cancelToggle = function () {
    $this->confirmingToggle = null;
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Organizadores</h2>
            <a href="{{ route('users.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + Nuevo organizador
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <x-alert type="success" :message="session('success')" class="mb-4" />
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button wire:click="confirmToggle({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay organizadores registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($confirmingToggle)
        <x-confirm-modal
            title="Confirmar cambio de estado"
            message="¿Estás seguro de que deseas cambiar el estado de este usuario?"
            confirm-text="Confirmar"
            cancel-text="Cancelar"
        >
            <x-slot name="actions">
                <button wire:click="toggleStatus({{ $confirmingToggle }})" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Confirmar</button>
                <button wire:click="cancelToggle" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm">Cancelar</button>
            </x-slot>
        </x-confirm-modal>
    @endif
</div>
