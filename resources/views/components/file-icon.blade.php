{{-- Icône du format d'un document : <x-file-icon :document="$doc" class="text-sm" /> ou :path="..." --}}
@props(['document' => null, 'path' => null])
@php $type = \App\Support\FileType::fromPath($document?->file_path ?? $path); @endphp
<i {{ $attributes->merge(['class' => 'fa-solid ' . $type->icon() . ' ' . $type->color()]) }} title="{{ $type->label() }}"></i>
