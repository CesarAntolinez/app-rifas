<?php

use App\Models\Raffle;
use App\Models\RaffleAuditLog;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state(['raffle' => null, 'auditLog' => null]);

mount(function (Raffle $raffle) {
    $this->authorize('view', $raffle);
    $this->raffle = $raffle;
    $this->auditLog = $raffle->auditLog()->with('executedBy')->first();
});

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Auditoría — {{ $raffle->name }}</h2>
            <button onclick="window.print()" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 print:hidden">
                🖨️ Imprimir / PDF
            </button>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 print:py-0 print:max-w-full">
        @if (! $auditLog)
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No hay registro de auditoría para este sorteo.</p>
            </div>
        @else
            <!-- Audit Header -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Información del sorteo</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Ejecutado por</dt>
                        <dd class="mt-1 text-gray-900">{{ $auditLog->executedBy->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Fecha de ejecución</dt>
                        <dd class="mt-1 text-gray-900">{{ $auditLog->executed_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Versión del algoritmo</dt>
                        <dd class="mt-1 font-mono text-gray-900">{{ $auditLog->algorithm_version }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Información de semilla</dt>
                        <dd class="mt-1 font-mono text-gray-900">{{ $auditLog->seed_info }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Total boletos</dt>
                        <dd class="mt-1 text-gray-900">{{ $auditLog->payload['total_tickets'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Total premios</dt>
                        <dd class="mt-1 text-gray-900">{{ $auditLog->payload['total_prizes'] ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Winners Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Registro de ganadores</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Premio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Participante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Boleto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($auditLog->payload['winners'] ?? [] as $winner)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $winner['prize_name'] }}</td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $winner['serie'] }}</td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $winner['number'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $winner['participant_id'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $winner['ticket_id'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay datos de ganadores.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Raw Payload (read-only) -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Payload completo (solo lectura)</h3>
                <pre class="text-xs text-gray-600 bg-gray-50 p-4 rounded overflow-auto max-h-64">{{ json_encode($auditLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        <div class="mt-4 print:hidden">
            <a href="{{ route('raffles.results', $raffle) }}" wire:navigate class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">← Ver resultados</a>
        </div>
    </div>
</div>
