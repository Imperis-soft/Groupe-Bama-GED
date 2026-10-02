{{-- Segments surlignés (App\Services\DocumentSearch::highlight / snippet), sans espace ajouté entre les segments --}}
@props(['segments' => []])
<?php foreach ($segments as $segment) { echo $segment['hit'] ? '<mark class="bg-amber-100 text-slate-900 rounded-sm px-0.5">' . e($segment['text']) . '</mark>' : e($segment['text']); } ?>
