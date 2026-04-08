<?php

use App\Models\Participant;
use App\Models\Raffle;
use App\Services\NotificationService;
use App\Services\TicketService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'raffle' => null,
    'name' => '',
    'email' => '',
    'phone' => '',
    'tickets' => [['serie' => '', 'number' => '']],
]);

mount(function (Raffle $raffle) {
    $this->authorize('create', [Participant::class, $raffle]);
    $this->raffle = $raffle;
});

rules([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email'],
    'phone' => ['nullable', 'string', 'max:20'],
    'tickets' => ['required', 'array', 'min:1'],
    'tickets.*.serie' => ['required', 'string'],
    'tickets.*.number' => ['required', 'string'],
]);

$addTicket = function () {
    $this->tickets[] = ['serie' => '', 'number' => ''];
};

$removeTicket = function (int $index) {
    array_splice($this->tickets, $index, 1);
    $this->tickets = array_values($this->tickets);
};

$save = function (TicketService $ticketService, NotificationService $notificationService) {
    $this->authorize('create', [Participant::class, $this->raffle]);
    $this->validate();

    $existing = Participant::where('raffle_id', $this->raffle->id)->where('email', $this->email)->exists();
    if ($existing) {
        $this->addError('email', 'Este email ya está registrado en este sorteo.');
        return;
    }

    $participant = Participant::create([
        'raffle_id' => $this->raffle->id,
        'name' => $this->name,
        'email' => $this->email,
        'phone' => $this->phone,
    ]);

    foreach ($this->tickets as $ticketData) {
        $ticketService->generateTicket($this->raffle, $participant, $ticketData['serie'], $ticketData['number']);
    }

    $notificationService->notifyParticipationConfirmed($participant, $this->raffle);

    session()->flash('success', 'Participante agregado exitosamente.');
    $this->redirectRoute('participants.index', $this->raffle, navigate: true);
};

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Agregar participante — {{ $raffle->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('Nombre completo')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Teléfono (opcional)')" />
                        <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="tel" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <!-- Tickets -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Boletos</h4>
                        @foreach ($tickets as $index => $ticket)
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-1">
                                    <x-input-label :value="__('Serie')" />
                                    <x-text-input wire:model="tickets.{{ $index }}.serie" class="block mt-1 w-full" type="text" placeholder="A, B, C..." />
                                    <x-input-error :messages="$errors->get('tickets.' . $index . '.serie')" class="mt-1" />
                                </div>
                                <div class="flex-1">
                                    <x-input-label :value="__('Número')" />
                                    <x-text-input wire:model="tickets.{{ $index }}.number" class="block mt-1 w-full" type="text" placeholder="{{ $raffle->ticket_digit_type === 'double' ? '00-99' : ($raffle->ticket_digit_type === 'triple' ? '000-999' : '0000-9999') }}" />
                                    <x-input-error :messages="$errors->get('tickets.' . $index . '.number')" class="mt-1" />
                                </div>
                                @if (count($tickets) > 1)
                                    <div class="pt-6">
                                        <button wire:click="removeTicket({{ $index }})" type="button" class="text-red-400 hover:text-red-600">✕</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <button wire:click="addTicket" type="button" class="w-full py-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg text-sm hover:border-indigo-400 hover:text-indigo-600">+ Agregar boleto</button>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('participants.index', $raffle) }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</a>
                    <x-primary-button wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Agregar participante</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
