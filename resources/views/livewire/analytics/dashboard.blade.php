<?php

use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('layouts.app');

state(['data' => []]);

mount(function (AnalyticsService $analyticsService) {
    $user = Auth::user();

    if ($user->isAdmin()) {
        $this->data = $analyticsService->getAdminDashboard();
    } else {
        $this->data = $analyticsService->getOrganizerDashboard($user);
    }
});

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Analíticas</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @if (auth()->user()->isAdmin())
                <div class="bg-white shadow rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">Total participantes</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $data['total_participants'] ?? 0 }}</dd>
                </div>
                <div class="bg-white shadow rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">Ejecutados este mes</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $data['executed_this_month'] ?? 0 }}</dd>
                </div>
            @endif

            @foreach ($data['raffles_by_status'] ?? [] as $status => $count)
                <div class="bg-white shadow rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500 capitalize">Sorteos {{ $status }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">{{ $count }}</dd>
                </div>
            @endforeach
        </div>

        @if (auth()->user()->isOrganizer())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Raffles -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Top sorteos por participantes</h3>
                    @forelse ($data['top_by_participants'] ?? [] as $raffle)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $raffle->name }}</p>
                                <x-raffle-status-badge :status="$raffle->status" />
                            </div>
                            <span class="text-sm font-semibold text-gray-700">{{ $raffle->participants_count }} part.</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sin datos aún.</p>
                    @endforelse
                </div>

                <!-- Next Closings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Próximos cierres</h3>
                    @forelse ($data['next_closings'] ?? [] as $raffle)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <p class="text-sm font-medium text-gray-900">{{ $raffle->name }}</p>
                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($raffle->ends_at)->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay sorteos activos próximos a cerrar.</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if (auth()->user()->isAdmin())
            <!-- Status Distribution Bar Chart -->
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Distribución de sorteos por estado</h3>
                @php
                    $statusColors = ['draft' => 'bg-gray-400', 'active' => 'bg-green-500', 'closed' => 'bg-orange-500', 'executed' => 'bg-blue-500', 'deleted' => 'bg-red-500'];
                    $total = array_sum($data['raffles_by_status'] ?? []);
                @endphp
                @if ($total > 0)
                    <div class="flex rounded-full overflow-hidden h-6 mb-3">
                        @foreach ($data['raffles_by_status'] ?? [] as $status => $count)
                            <div class="{{ $statusColors[$status] ?? 'bg-gray-300' }}" style="width: {{ ($count / $total) * 100 }}%" title="{{ $status }}: {{ $count }}"></div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($data['raffles_by_status'] ?? [] as $status => $count)
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 rounded-sm mr-1 {{ $statusColors[$status] ?? 'bg-gray-300' }}"></div>
                                <span class="capitalize text-gray-600">{{ $status }}: {{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Sin datos aún.</p>
                @endif
            </div>
        @endif
    </div>
</div>
