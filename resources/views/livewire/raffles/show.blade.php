<?php

use App\Models\Raffle;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state(['raffle' => null]);

mount(function (Raffle $raffle) {
    $this->authorize('view', $raffle);
    $this->raffle = $raffle->load(['prizes', 'owner'])->loadCount('participants', 'tickets');
});

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-semibold text-gray-800">{{ $raffle->name }}</h2>
                <x-raffle-status-badge :status="$raffle->status" />
            </div>
            <div class="flex items-center space-x-2">
                @can('update', $raffle)
                    <a href="{{ route('raffles.edit', $raffle) }}" wire:navigate class="px-3 py-2 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">Editar</a>
                @endcan
                @can('publish', $raffle)
                    <button wire:click="$dispatch('publish', { id: {{ $raffle->id }} })" class="px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">Publicar</button>
                @endcan
                @can('close', $raffle)
                    <button wire:click="$dispatch('close', { id: {{ $raffle->id }} })" class="px-3 py-2 text-sm bg-orange-600 text-white rounded-md hover:bg-orange-700">Cerrar sorteo</button>
                @endcan
                @can('execute', $raffle)
                    <a href="{{ route('raffles.execute', $raffle) }}" wire:navigate class="px-3 py-2 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700">Ejecutar sorteo</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Información general</h3>
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Organizador</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $raffle->owner->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Tipo de boleto</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $raffle->ticket_digit_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Series</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $raffle->series_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Inicia</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $raffle->starts_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Cierra</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $raffle->ends_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                    @if ($raffle->description)
                        <div class="mt-4">
                            <dt class="text-xs font-medium text-gray-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $raffle->description }}</dd>
                        </div>
                    @endif
                </div>

                <!-- Prizes -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Premios</h3>
                    @forelse ($raffle->prizes->sortBy('order') as $prize)
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $prize->name }}</p>
                                @if ($prize->description)
                                    <p class="text-xs text-gray-500">{{ $prize->description }}</p>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">{{ $prize->winner_count }} ganador(es)</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay premios configurados.</p>
                    @endforelse
                </div>
            </div>

            <!-- Stats -->
            <div class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Estadísticas</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Participantes</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $raffle->participants_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Boletos</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $raffle->tickets_count }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Acciones</h3>
                    <div class="space-y-2">
                        <a href="{{ route('participants.index', $raffle) }}" wire:navigate class="block w-full text-center px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Ver participantes</a>
                        @if ($raffle->status === 'executed')
                            <a href="{{ route('raffles.results', $raffle) }}" wire:navigate class="block w-full text-center px-4 py-2 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">Ver resultados</a>
                            <a href="{{ route('raffles.audit', $raffle) }}" wire:navigate class="block w-full text-center px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Auditoría</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
