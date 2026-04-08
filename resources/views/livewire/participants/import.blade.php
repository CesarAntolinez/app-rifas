<?php

use App\Models\Participant;
use App\Models\Raffle;
use App\Services\TicketService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\usesFileUploads;

layout('layouts.app');
usesFileUploads();

state([
    'raffle' => null,
    'file' => null,
    'processing' => false,
    'results' => null,
]);

mount(function (Raffle $raffle) {
    $this->authorize('create', [Participant::class, $raffle]);
    $this->raffle = $raffle;
});

$import = function (TicketService $ticketService) {
    $this->authorize('create', [Participant::class, $this->raffle]);
    $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

    $this->processing = true;
    $processed = 0;
    $rejected = [];

    $path = $this->file->getRealPath();
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle); // skip header

    while (($row = fgetcsv($handle)) !== false) {
        $line = $processed + 2;
        try {
            if (count($row) < 3) {
                $rejected[] = "Línea {$line}: datos insuficientes";
                continue;
            }

            [$name, $email, $phone, $serie, $number] = array_pad($row, 5, '');

            $email = trim($email);
            if (empty($name) || empty($email) || empty($serie) || empty($number)) {
                $rejected[] = "Línea {$line}: campos requeridos vacíos";
                continue;
            }

            $participant = Participant::firstOrCreate(
                ['raffle_id' => $this->raffle->id, 'email' => $email],
                ['name' => trim($name), 'phone' => trim($phone) ?: null]
            );

            $ticketService->generateTicket($this->raffle, $participant, trim($serie), trim($number));
            $processed++;
        } catch (\Exception $e) {
            $rejected[] = "Línea {$line}: " . $e->getMessage();
        }
    }

    fclose($handle);
    $this->processing = false;
    $this->results = ['processed' => $processed, 'rejected' => $rejected];
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Importar participantes — {{ $raffle->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Formato del archivo CSV</h3>
                <p class="text-xs text-gray-500">El archivo debe contener las columnas en este orden:</p>
                <code class="block mt-1 text-xs bg-gray-50 p-2 rounded">nombre, email, telefono, serie, numero</code>
            </div>

            @if ($results)
                <div class="mb-6">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-md mb-3">
                        <p class="text-sm font-medium text-green-800">✓ {{ $results['processed'] }} participante(s) procesados correctamente</p>
                    </div>
                    @if (count($results['rejected']) > 0)
                        <div class="p-4 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm font-medium text-red-800 mb-2">{{ count($results['rejected']) }} registro(s) rechazados:</p>
                            <ul class="text-xs text-red-700 space-y-1">
                                @foreach ($results['rejected'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <form wire:submit="import">
                <div>
                    <x-input-label for="file" :value="__('Archivo CSV')" />
                    <input wire:model="file" id="file" type="file" accept=".csv,.txt" class="block mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700">
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('participants.index', $raffle) }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Volver</a>
                    <x-primary-button wire:loading.attr="disabled" wire:target="import">
                        @if ($processing)
                            <span>Procesando...</span>
                        @else
                            <span wire:loading.remove wire:target="import">Importar</span>
                            <span wire:loading wire:target="import">Procesando...</span>
                        @endif
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
