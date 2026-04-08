@props(['paginator' => null])

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            {{ $slot }}
        </table>
    </div>
    @if ($paginator && $paginator->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
