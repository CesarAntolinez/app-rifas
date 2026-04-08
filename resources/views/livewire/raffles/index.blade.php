<?php

use App\Models\Raffle;
use App\Services\RaffleService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'raffles' => [],
    'filterStatus' => '',
    'filterSearch' => '',
    'filterDateFrom' => '',
    'filterDateTo' => '',
    'confirmingDelete' => null,
]);

mount(function () {
    $this->authorize('viewAny', Raffle::class);
    $this->loadRaffles();
});

$loadRaffles = function () {
    $query = Raffle::scopeForUser(Raffle::query(), auth()->user())
        ->withCount('participants')
        ->orderBy('created_at', 'desc');

    if ($this->filterStatus) {
        $query->where('status', $this->filterStatus);
    }

    if ($this->filterSearch) {
        $query->where('name', 'like', '%' . $this->filterSearch . '%');
    }

    if ($this->filterDateFrom) {
        $query->where('ends_at', '>=', $this->filterDateFrom);
    }

    if ($this->filterDateTo) {
        $query->where('ends_at', '<=', $this->filterDateTo . ' 23:59:59');
    }

    $this->raffles = $query->get();
};

$updatedFilterStatus = function () { $this->loadRaffles(); };
$updatedFilterSearch = function () { $this->loadRaffles(); };
$updatedFilterDateFrom = function () { $this->loadRaffles(); };
$updatedFilterDateTo = function () { $this->loadRaffles(); };

$confirmDelete = function (int $raffleId) {
    $this->confirmingDelete = $raffleId;
};

$cancelDelete = function () {
    $this->confirmingDelete = null;
};

$deleteRaffle = function (RaffleService $raffleService) {
    $raffle = Raffle::findOrFail($this->confirmingDelete);
    $this->authorize('delete', $raffle);
    $raffleService->deleteRaffle($raffle, auth()->user());
    $this->confirmingDelete = null;
    $this->loadRaffles();
};

$publishRaffle = function (int $raffleId, RaffleService $raffleService) {
    $raffle = Raffle::findOrFail($raffleId);
    $this->authorize('publish', $raffle);
    $raffleService->publishRaffle($raffle, auth()->user());
    $this->loadRaffles();
};

$closeRaffle = function (int $raffleId, RaffleService $raffleService) {
    $raffle = Raffle::findOrFail($raffleId);
    $this->authorize('close', $raffle);
    $raffleService->closeRaffle($raffle, auth()->user());
    $this->loadRaffles();
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Sorteos</h2>
            @can('create', App\Models\Raffle::class)
                <a href="{{ route('raffles.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + Nuevo sorteo
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                    <input wire:model.live.debounce.300ms="filterSearch" type="text" placeholder="Nombre del sorteo..." class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todos</option>
                        <option value="draft">Borrador</option>
                        <option value="active">Activo</option>
                        <option value="closed">Cerrado</option>
                        <option value="executed">Ejecutado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cierra desde</label>
                    <input wire:model.live="filterDateFrom" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cierra hasta</label>
                    <input wire:model.live="filterDateTo" type="date" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        <!-- Raffle Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($raffles as $raffle)
                <div class="bg-white shadow rounded-lg overflow-hidden flex flex-col">
                    @if ($raffle->image_path)
                        <img src="{{ Storage::url($raffle->image_path) }}" alt="{{ $raffle->name }}" class="w-full h-40 object-cover">
                    @else
                        <div class="w-full h-40 bg-gray-100 flex items-center justify-center text-gray-400">
                            <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-900 truncate flex-1 mr-2">{{ $raffle->name }}</h3>
                            <x-raffle-status-badge :status="$raffle->status" />
                        </div>

                        <p class="text-xs text-gray-500 mb-3">
                            Cierra: {{ $raffle->ends_at->format('d/m/Y H:i') }}
                        </p>

                        <p class="text-xs text-gray-500 mb-4">
                            {{ $raffle->participants_count }} participante(s)
                        </p>

                        <div class="mt-auto flex flex-wrap gap-2">
                            <a href="{{ route('raffles.show', $raffle) }}" wire:navigate class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Ver</a>

                            @can('update', $raffle)
                                <a href="{{ route('raffles.edit', $raffle) }}" wire:navigate class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Editar</a>
                            @endcan

                            @can('publish', $raffle)
                                <button wire:click="publishRaffle({{ $raffle->id }})" class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Publicar</button>
                            @endcan

                            @can('close', $raffle)
                                <button wire:click="closeRaffle({{ $raffle->id }})" class="text-xs px-2 py-1 bg-orange-100 text-orange-700 rounded hover:bg-orange-200">Cerrar</button>
                            @endcan

                            @can('execute', $raffle)
                                <a href="{{ route('raffles.execute', $raffle) }}" wire:navigate class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200">Ejecutar</a>
                            @endcan

                            @can('delete', $raffle)
                                <button wire:click="confirmDelete({{ $raffle->id }})" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Eliminar</button>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-500">
                    No hay sorteos para mostrar.
                </div>
            @endforelse
        </div>
    </div>

    @if ($confirmingDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data>
            <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Eliminar sorteo</h3>
                <p class="text-sm text-gray-600 mb-6">¿Estás seguro de que deseas eliminar este sorteo? Esta acción no se puede deshacer.</p>
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button wire:click="deleteRaffle" class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
