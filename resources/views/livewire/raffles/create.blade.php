<?php

use App\Models\Raffle;
use App\Services\RaffleService;
use Illuminate\Support\Facades\Storage;

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\usesFileUploads;

layout('layouts.app');
usesFileUploads();

state([
    'step' => 1,
    // Step 1
    'name' => '',
    'description' => '',
    'starts_at' => '',
    'ends_at' => '',
    'image' => null,
    // Step 2
    'prizes' => [['name' => '', 'description' => '', 'winner_count' => 1, 'order' => 0]],
    // Step 3
    'ticket_digit_type' => 'double',
    'series_count' => 1,
]);

$step1Rules = [];
$step2Rules = [];
$step3Rules = [];
$nextStep = function () {
    if ($this->step === 1) {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    } elseif ($this->step === 2) {
        $this->validate([
            'prizes' => ['required', 'array', 'min:1'],
            'prizes.*.name' => ['required', 'string', 'max:255'],
            'prizes.*.winner_count' => ['required', 'integer', 'min:1'],
            'prizes.*.order' => ['required', 'integer', 'min:0'],
        ]);
    }
    $this->step++;
};

$prevStep = function () {
    $this->step--;
};

$addPrize = function () {
    $this->prizes[] = ['name' => '', 'description' => '', 'winner_count' => 1, 'order' => count($this->prizes)];
};

$removePrize = function (int $index) {
    array_splice($this->prizes, $index, 1);
    $this->prizes = array_values($this->prizes);
};

$save = function (RaffleService $raffleService) {
    $this->authorize('create', Raffle::class);

    $this->validate([
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'starts_at' => ['required', 'date'],
        'ends_at' => ['required', 'date', 'after:starts_at'],
        'prizes' => ['required', 'array', 'min:1'],
        'prizes.*.name' => ['required', 'string', 'max:255'],
        'prizes.*.winner_count' => ['required', 'integer', 'min:1'],
        'prizes.*.order' => ['required', 'integer', 'min:0'],
        'ticket_digit_type' => ['required', 'in:double,triple,quadruple'],
        'series_count' => ['required', 'integer', 'min:1', 'max:100'],
    ]);

    $imagePath = null;
    if ($this->image) {
        $imagePath = $this->image->store('raffles', 'private');
    }

    $raffle = $raffleService->createRaffle([
        'name' => $this->name,
        'description' => $this->description,
        'starts_at' => $this->starts_at,
        'ends_at' => $this->ends_at,
        'image_path' => $imagePath,
        'ticket_digit_type' => $this->ticket_digit_type,
        'series_count' => $this->series_count,
    ], auth()->user());

    foreach ($this->prizes as $prizeData) {
        $raffle->prizes()->create($prizeData);
    }

    session()->flash('success', 'Sorteo creado exitosamente.');
    $this->redirectRoute('raffles.show', $raffle, navigate: true);
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Crear sorteo</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Step Indicator -->
        <div class="flex items-center mb-8">
            @foreach (['Información', 'Premios', 'Boletos'] as $i => $label)
                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $step > $i + 1 ? 'bg-indigo-600 text-white' : ($step === $i + 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                        {{ $i + 1 }}
                    </div>
                    <span class="ml-2 text-sm font-medium {{ $step === $i + 1 ? 'text-indigo-600' : 'text-gray-500' }}">{{ $label }}</span>
                    @if (! $loop->last)
                        <div class="flex-1 h-px bg-gray-200 mx-4"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <!-- Step 1 -->
            @if ($step === 1)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del sorteo</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="description" :value="__('Descripción (opcional)')" />
                            <textarea wire:model="description" id="description" rows="3" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="starts_at" :value="__('Fecha inicio')" />
                                <x-text-input wire:model="starts_at" id="starts_at" class="block mt-1 w-full" type="datetime-local" />
                                <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="ends_at" :value="__('Fecha cierre')" />
                                <x-text-input wire:model="ends_at" id="ends_at" class="block mt-1 w-full" type="datetime-local" />
                                <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="image" :value="__('Imagen (opcional)')" />
                            <input wire:model="image" id="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="block mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700">
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2 -->
            @if ($step === 2)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Premios</h3>
                    <div class="space-y-4">
                        @foreach ($prizes as $index => $prize)
                            <div class="border border-gray-200 rounded-lg p-4 relative">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Nombre del premio')" />
                                        <x-text-input wire:model="prizes.{{ $index }}.name" class="block mt-1 w-full" type="text" />
                                        <x-input-error :messages="$errors->get('prizes.' . $index . '.name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label :value="__('Ganadores')" />
                                        <x-text-input wire:model="prizes.{{ $index }}.winner_count" class="block mt-1 w-full" type="number" min="1" />
                                        <x-input-error :messages="$errors->get('prizes.' . $index . '.winner_count')" class="mt-1" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <x-input-label :value="__('Descripción (opcional)')" />
                                    <textarea wire:model="prizes.{{ $index }}.description" rows="2" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                                </div>
                                <div class="mt-3">
                                    <x-input-label :value="__('Orden')" />
                                    <x-text-input wire:model="prizes.{{ $index }}.order" class="block mt-1 w-24" type="number" min="0" />
                                </div>
                                @if (count($prizes) > 1)
                                    <button wire:click="removePrize({{ $index }})" type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600 text-xs">✕</button>
                                @endif
                            </div>
                        @endforeach
                        <button wire:click="addPrize" type="button" class="w-full py-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg text-sm hover:border-indigo-400 hover:text-indigo-600">
                            + Agregar premio
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 3 -->
            @if ($step === 3)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración de boletos</h3>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="ticket_digit_type" :value="__('Tipo de boleto')" />
                            <select wire:model="ticket_digit_type" id="ticket_digit_type" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="double">Doble (00-99)</option>
                                <option value="triple">Triple (000-999)</option>
                                <option value="quadruple">Cuádruple (0000-9999)</option>
                            </select>
                            <x-input-error :messages="$errors->get('ticket_digit_type')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="series_count" :value="__('Cantidad de series')" />
                            <x-text-input wire:model="series_count" id="series_count" class="block mt-1 w-full" type="number" min="1" max="100" />
                            <x-input-error :messages="$errors->get('series_count')" class="mt-2" />
                        </div>
                    </div>
                </div>
            @endif

            <!-- Navigation -->
            <div class="flex justify-between mt-6">
                <div>
                    @if ($step > 1)
                        <button wire:click="prevStep" type="button" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">← Anterior</button>
                    @endif
                </div>
                <div>
                    @if ($step < 3)
                        <button wire:click="nextStep" type="button" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Siguiente →</button>
                    @else
                        <button wire:click="save" wire:loading.attr="disabled" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            <span wire:loading.remove wire:target="save">Crear sorteo</span>
                            <span wire:loading wire:target="save">Creando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
