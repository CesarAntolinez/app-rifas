<?php

use App\Models\Participant;
use App\Models\Raffle;
use App\Services\TicketService;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state([
    'raffle' => null,
    'participants' => [],
    'search' => '',
    'perPage' => 20,
    'page' => 1,
    'confirmingDelete' => null,
]);

mount(function (Raffle $raffle) {
    $this->authorize('viewAny', [Participant::class, $raffle]);
    $this->raffle = $raffle;
    $this->loadParticipants();
});

$loadParticipants = function () {
    $query = Participant::where('raffle_id', $this->raffle->id)
        ->withCount('tickets');

    if ($this->search) {
        $query->where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%')
              ->orWhere('email', 'like', '%' . $this->search . '%');
        });
    }

    $this->participants = $query->paginate($this->perPage, ['*'], 'page', $this->page);
};

$updatedSearch = function () {
    $this->page = 1;
    $this->loadParticipants();
};

$confirmDelete = function (int $participantId) {
    $this->confirmingDelete = $participantId;
};

$cancelDelete = function () {
    $this->confirmingDelete = null;
};

$deleteParticipant = function () {
    $participant = Participant::findOrFail($this->confirmingDelete);
    $this->authorize('delete', [$participant, $this->raffle]);
    $participant->delete();
    $this->confirmingDelete = null;
    $this->loadParticipants();
};

$exportCsv = function () {
    $participants = Participant::where('raffle_id', $this->raffle->id)
        ->withCount('tickets')
        ->get();

    $filename = 'participantes_' . $this->raffle->id . '_' . now()->format('Ymd') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ];

    $callback = function () use ($participants) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['ID', 'Nombre', 'Email', 'Teléfono', 'Boletos', 'Registrado']);
        foreach ($participants as $p) {
            fputcsv($file, [$p->id, $p->name, $p->email, $p->phone, $p->tickets_count, $p->created_at->format('d/m/Y H:i')]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Participantes — {{ $raffle->name }}</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="exportCsv" class="px-3 py-2 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200">Exportar CSV</button>
                <a href="{{ route('participants.import', $raffle) }}" wire:navigate class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Importar CSV</a>
                <a href="{{ route('participants.create', $raffle) }}" wire:navigate class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">+ Agregar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre o email..." class="w-full sm:w-80 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boletos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($participants as $participant)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $participant->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $participant->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $participant->phone ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $participant->tickets_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button wire:click="confirmDelete({{ $participant->id }})" class="text-red-600 hover:text-red-900">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay participantes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($participants instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $participants->links() }}
                </div>
            @endif
        </div>
    </div>

    @if ($confirmingDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-semibold mb-2">Eliminar participante</h3>
                <p class="text-sm text-gray-600 mb-6">¿Estás seguro? Se eliminarán también sus boletos asignados.</p>
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm bg-gray-100 rounded-md">Cancelar</button>
                    <button wire:click="deleteParticipant" class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
