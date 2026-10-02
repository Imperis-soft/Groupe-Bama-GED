{{-- Accès par service (pages création / édition de catégorie) --}}
@php
    $currentAccess = old('access', isset($category) ? $category->departments->pluck('pivot.access_level', 'id')->all() : []);
    $isPublic = old('manage_access') ? (bool) old('is_public') : (bool) ($category->is_public ?? false);
@endphp
<input type="hidden" name="manage_access" value="1">

<div class="pt-6 border-t border-gray-50 space-y-4">
    <div>
        <h2 class="text-[11px] font-black text-gray-400 uppercase tracking-widest ml-1">Qui peut accéder à cette catégorie ?</h2>
        <p class="text-[10px] text-gray-400 mt-1 ml-1">Les droits s'appliquent aussi aux sous-catégories. Les documents confidentiels ne sont jamais ouverts par service.</p>
    </div>

    <label class="flex items-start gap-3 bg-gray-50 rounded-2xl px-5 py-4 cursor-pointer">
        <input type="hidden" name="is_public" value="0">
        <input type="checkbox" name="is_public" value="1" {{ $isPublic ? 'checked' : '' }}
               class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
        <span>
            <span class="block text-sm font-bold text-gray-800">Visible par tous les membres de l'entreprise</span>
            <span class="block text-[10px] text-gray-400">En consultation (ex. notes de service, procédures, règlement intérieur).</span>
        </span>
    </label>

    @if($departments->isEmpty())
    <p class="text-[11px] text-gray-400 ml-1">
        Aucun service disponible : contactez {{ config('saas.vendor_name') }}.
        pour ouvrir cette catégorie à une équipe.
    </p>
    @else
    <div class="divide-y divide-gray-50 border border-gray-100 rounded-2xl overflow-hidden">
        @foreach($departments as $department)
        @php $current = $currentAccess[$department->id] ?? ''; @endphp
        <div class="flex items-center justify-between gap-3 px-5 py-3">
            <span class="text-sm font-bold text-gray-700 truncate">
                <i class="fa-solid fa-sitemap text-orange-400 text-xs mr-1.5"></i>{{ $department->name }}
            </span>
            <div class="inline-flex bg-gray-100 rounded-lg p-0.5 shrink-0">
                @foreach(['' => 'Aucun', 'view' => 'Consultation', 'edit' => 'Modification'] as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="access[{{ $department->id }}]" value="{{ $value }}" class="peer sr-only" {{ $current === $value ? 'checked' : '' }}>
                    <span class="block px-2.5 py-1 rounded-md text-[10px] font-bold text-gray-400 transition-all
                                 peer-checked:bg-white peer-checked:shadow-sm {{ $value === 'edit' ? 'peer-checked:text-orange-600' : 'peer-checked:text-gray-800' }}">
                        {{ $label }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    <p class="text-[10px] text-gray-400 ml-1 italic">Une fois des droits définis, seuls les services avec « Modification » (et les administrateurs) peuvent y ranger de nouveaux documents.</p>
    @endif
</div>
