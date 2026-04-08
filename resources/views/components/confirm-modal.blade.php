@props([
    'title' => 'Confirmar',
    'message' => '¿Estás seguro?',
    'confirmText' => 'Confirmar',
    'cancelText' => 'Cancelar',
])

<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data>
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        <p class="text-sm text-gray-600 mb-6">{{ $message }}</p>
        @if (isset($actions))
            <div class="flex justify-end space-x-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
