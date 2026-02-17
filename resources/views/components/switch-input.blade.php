@props([
    'model' => 'status',
    'label' => 'Ativar/Desativar',
])

<div x-data="{ localStatus: @entangle($model).live }" class="flex items-center">
    <span class="mr-2 text-sm text-gray-700">{{ $label }}</span>
    <button
        type="button"
        @click="localStatus = !localStatus"
        class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors duration-200 ease-in-out"
        :class="localStatus ? 'bg-indigo-600' : 'bg-gray-300'">
        <span
            class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-200 ease-in-out"
            :class="localStatus ? 'translate-x-6' : 'translate-x-1'">
        </span>
    </button>
</div>

