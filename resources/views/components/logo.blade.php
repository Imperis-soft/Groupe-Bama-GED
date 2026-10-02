{{-- Logo GED (empreinte digitale). Taille via la classe, ex. <x-logo class="w-9 h-9" /> --}}
@props(['large' => false])
<img src="{{ asset($large ? 'images/logo-ged.webp' : 'images/logo-ged-160.webp') }}"
     alt="{{ config('saas.platform_name') }}" draggable="false"
     {{ $attributes->merge(['class' => 'object-contain select-none shrink-0']) }}>
