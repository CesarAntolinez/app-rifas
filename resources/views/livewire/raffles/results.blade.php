<?php

use App\Models\Raffle;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state(['raffle' => null, 'prizes' => []]);

mount(function (Raffle $raffle) {
    $this->authorize('view', $raffle);
    $this->raffle = $raffle;
    $this->prizes = $raffle->prizes()
        ->with(['winners.ticket.participant'])
        ->orderBy('order')
        ->get();
});

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Resultados — {{ $raffle->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @foreach ($prizes as $prize)
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    🏆 {{ $prize->name }}
                    <span class="ml-2 text-sm font-normal text-gray-500">({{ $prize->winner_count }} ganador(es))</span>
                </h3>

                @if ($prize->winners->isEmpty())
                    <p class="text-sm text-gray-500">No hay ganadores registrados para este premio.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($prize->winners as $result)
                            <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $result->ticket->participant->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $result->ticket->participant->email }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono font-bold text-indigo-700">Serie {{ $result->ticket->serie }} — #{{ $result->ticket->number }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex justify-between">
            <a href="{{ route('raffles.show', $raffle) }}" wire:navigate class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">← Volver</a>
            <a href="{{ route('raffles.audit', $raffle) }}" wire:navigate class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Ver auditoría →</a>
        </div>
    </div>
</div>
