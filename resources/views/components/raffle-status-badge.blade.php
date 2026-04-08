@props(['status'])

@php
    $colors = [
        'draft' => 'bg-gray-100 text-gray-700',
        'active' => 'bg-green-100 text-green-700',
        'closed' => 'bg-orange-100 text-orange-700',
        'executed' => 'bg-blue-100 text-blue-700',
        'deleted' => 'bg-red-100 text-red-700',
    ];

    $labels = [
        'draft' => 'Borrador',
        'active' => 'Activo',
        'closed' => 'Cerrado',
        'executed' => 'Ejecutado',
        'deleted' => 'Eliminado',
    ];

    $colorClass = $colors[$status] ?? 'bg-gray-100 text-gray-700';
    $label = $labels[$status] ?? ucfirst($status);
@endphp

<span {{ $attributes->merge(['class' => "px-2 inline-flex text-xs leading-5 font-semibold rounded-full $colorClass"]) }}>
    {{ $label }}
</span>
