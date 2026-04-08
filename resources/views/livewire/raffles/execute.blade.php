<?php

use App\Models\Raffle;
use App\Services\DrawExecutionService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'raffle' => null,
    'confirmation' => '',
    'executing' => false,
    'error' => null,
]);

mount(function (Raffle $raffle) {
    $this->authorize('execute', $raffle);
    $this->raffle = $raffle->load(['prizes', 'participants'])->loadCount('participants', 'tickets');
});

$execute = function (DrawExecutionService $drawService) {
    $this->authorize('execute', $this->raffle);

    if ($this->confirmation !== 'Confirmo que deseo ejecutar el sorteo') {
        $this->addError('confirmation', 'El texto de confirmación no coincide.');
        return;
    }

    $this->executing = true;
    $this->error = null;

    try {
        $auditLog = $drawService->execute($this->raffle, auth()->user());
        session()->flash('success', 'Sorteo ejecutado exitosamente.');
        $this->redirectRoute('raffles.results', $this->raffle, navigate: true);
    } catch (\Exception $e) {
        $this->error = $e->getMessage();
        $this->executing = false;
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Ejecutar sorteo — {{ $raffle->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <!-- Summary -->
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Resumen del sorteo</h3>
                <dl class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <dt class="text-xs font-medium text-gray-500">Participantes</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $raffle->participants_count }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <dt class="text-xs font-medium text-gray-500">Boletos</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $raffle->tickets_count }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Prizes -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Premios a sortear</h3>
                <div class="space-y-2">
                    @foreach ($raffle->prizes->sortBy('order') as $prize)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-900">{{ $prize->name }}</span>
                            <span class="text-xs text-gray-500">{{ $prize->winner_count }} ganador(es)</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($error)
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600">{{ $error }}</p>
                </div>
            @endif

            <!-- Confirmation -->
            <div class="mb-6">
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md mb-4">
                    <p class="text-sm text-yellow-800 font-medium">⚠️ Esta acción es irreversible. Una vez ejecutado el sorteo no podrá modificarse.</p>
                </div>
                <x-input-label for="confirmation" :value="__('Escribe exactamente: \"Confirmo que deseo ejecutar el sorteo\"')" />
                <x-text-input wire:model="confirmation" id="confirmation" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('raffles.show', $raffle) }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</a>
                <button wire:click="execute" wire:loading.attr="disabled" wire:target="execute" class="px-6 py-2 text-sm font-semibold bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50">
                    @if ($executing)
                        <span class="flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Ejecutando sorteo...
                        </span>
                    @else
                        <span wire:loading.remove wire:target="execute">🎲 Ejecutar sorteo</span>
                        <span wire:loading wire:target="execute" class="flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Ejecutando...
                        </span>
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>
