@props(['type' => 'info', 'message' => '', 'dismissible' => true])

@php
    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];

    $icons = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center p-4 mb-4 text-sm border rounded-lg ' . ($colors[$type] ?? $colors['info'])]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95">

    <i class="fa-solid {{ $icons[$type] ?? $icons['info'] }} mr-3 flex-shrink-0"></i>
    <div class="flex-1">{{ $message }}</div>

    @if($dismissible)
        <button @click="show = false"
                class="ml-3 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fa-solid fa-times"></i>
        </button>
    @endif
</div>