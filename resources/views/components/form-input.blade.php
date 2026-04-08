@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'error' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm' . ($error ? ' border-red-300 focus:ring-red-500 focus:border-red-500' : '')]) }}
    >
    @if ($error)
        <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
