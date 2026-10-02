{{-- Circuit imposé aux documents de la catégorie (vide : celui de la catégorie parente) --}}
@php $cat = $category ?? null; @endphp
<div>
    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Circuit obligatoire</label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach(['requires_approval' => 'Approbation', 'requires_signature' => 'Signature'] as $field => $label)
        @php $value = old($field, $cat?->$field === null ? '' : (int) $cat->$field); @endphp
        <div>
            <select name="{{ $field }}" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-4 py-3.5 text-sm text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0">
                <option value="" @selected($value === '')>{{ $label }} : comme la catégorie parente</option>
                <option value="1" @selected((string) $value === '1')>{{ $label }} obligatoire</option>
                <option value="0" @selected((string) $value === '0')>{{ $label }} facultative</option>
            </select>
        </div>
        @endforeach
    </div>
    <p class="text-[10px] text-gray-400 mt-1 ml-1">
        Approbation obligatoire : le circuit (modèle rattaché à la catégorie) démarre dès le dépôt, et le document ne peut être ni signé ni archivé avant d'être approuvé.
        Signature obligatoire : le document ne peut être archivé qu'une fois signé.
    </p>
</div>

