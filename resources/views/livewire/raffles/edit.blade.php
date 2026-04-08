<?php

use App\Models\Raffle;
use App\Services\RaffleService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\usesFileUploads;

layout('layouts.app');
usesFileUploads();

state([
    'raffle' => null,
    'name' => '',
    'description' => '',
    'starts_at' => '',
    'ends_at' => '',
    'image' => null,
    'prizes' => [],
]);

mount(function (Raffle $raffle) {
    $this->authorize('update', $raffle);
    $this->raffle = $raffle->load('prizes');
    $this->name = $raffle->name;
    $this->description = $raffle->description;
    $this->starts_at = $raffle->starts_at->format('Y-m-d\TH:i');
    $this->ends_at = $raffle->ends_at->format('Y-m-d\TH:i');
    $this->prizes = $raffle->prizes->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'description' => $p->description,
        'winner_count' => $p->winner_count,
        'order' => $p->order,
    ])->toArray();
});

rules([
    'name' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'starts_at' => ['required', 'date'],
    'ends_at' => ['required', 'date', 'after:starts_at'],
    'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    'prizes' => ['required', 'array', 'min:1'],
    'prizes.*.name' => ['required', 'string', 'max:255'],
    'prizes.*.winner_count' => ['required', 'integer', 'min:1'],
    'prizes.*.order' => ['required', 'integer', 'min:0'],
]);

$addPrize = function () {
    $this->prizes[] = ['id' => null, 'name' => '', 'description' => '', 'winner_count' => 1, 'order' => count($this->prizes)];
};

$removePrize = function (int $index) {
    array_splice($this->prizes, $index, 1);
    $this->prizes = array_values($this->prizes);
};

$save = function (RaffleService $raffleService) {
    $this->authorize('update', $this->raffle);
    $this->validate();

    $data = [
        'name' => $this->name,
        'description' => $this->description,
        'starts_at' => $this->starts_at,
        'ends_at' => $this->ends_at,
    ];

    if ($this->image) {
        $data['image_path'] = $this->image->store('raffles', 'private');
    }

    $raffleService->updateRaffle($this->raffle, $data);

    // Sync prizes
    $existingIds = collect($this->prizes)->pluck('id')->filter()->toArray();
    $this->raffle->prizes()->whereNotIn('id', $existingIds)->delete();

    foreach ($this->prizes as $prizeData) {
        if (! empty($prizeData['id'])) {
            $this->raffle->prizes()->where('id', $prizeData['id'])->update([
                'name' => $prizeData['name'],
                'description' => $prizeData['description'],
                'winner_count' => $prizeData['winner_count'],
                'order' => $prizeData['order'],
            ]);
        } else {
            $this->raffle->prizes()->create($prizeData);
        }
    }

    session()->flash('success', 'Sorteo actualizado exitosamente.');
    $this->redirectRoute('raffles.show', $this->raffle, navigate: true);
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar sorteo: {{ $raffle->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('Nombre')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Descripción')" />
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

                    <!-- Prizes -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Premios</h4>
                        @foreach ($prizes as $index => $prize)
                            <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Nombre')" />
                                        <x-text-input wire:model="prizes.{{ $index }}.name" class="block mt-1 w-full" type="text" />
                                        <x-input-error :messages="$errors->get('prizes.' . $index . '.name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label :value="__('Ganadores')" />
                                        <x-text-input wire:model="prizes.{{ $index }}.winner_count" class="block mt-1 w-full" type="number" min="1" />
                                    </div>
                                </div>
                                @if (count($prizes) > 1)
                                    <button wire:click="removePrize({{ $index }})" type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600 text-xs">✕</button>
                                @endif
                            </div>
                        @endforeach
                        <button wire:click="addPrize" type="button" class="w-full py-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg text-sm hover:border-indigo-400">+ Agregar premio</button>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('raffles.show', $raffle) }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</a>
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Guardar cambios</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
