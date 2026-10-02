{{-- Champs communs création / édition d'une entreprise --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label class="label">Nom de l'entreprise <span class="text-orange-600">*</span></label>
        <input type="text" name="name" value="{{ old('name', $organization->name ?? '') }}" required class="field" placeholder="Ex. : Groupe Bama"
               x-on:input="if (!prefixTouched) prefix = $event.target.value.normalize('NFD').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 6);
                          if (!bucketTouched) bucket = ('ged-' + $event.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')).slice(0, 63).replace(/-+$/, '')">
    </div>
    <div>
        <label class="label">Préfixe des références <span class="text-orange-600">*</span></label>
        <input type="text" name="reference_prefix" x-model="prefix" @input="prefixTouched = true" maxlength="10" required class="field uppercase font-mono"
               pattern="[A-Z0-9]+" title="Lettres majuscules et chiffres uniquement">
        <p class="hint">Aperçu : <span class="font-mono font-medium text-slate-700" x-text="(prefix || 'DOC') + '-A1B2C3'"></span></p>
    </div>
    <div>
        <label class="label">Email de contact</label>
        <input type="email" name="email" value="{{ old('email', $organization->email ?? '') }}" class="field" placeholder="contact@entreprise.com">
    </div>
    <div>
        <label class="label">Téléphone</label>
        <input type="text" name="phone" value="{{ old('phone', $organization->phone ?? '') }}" class="field" placeholder="+223 …">
    </div>
    <div>
        <label class="label">Adresse</label>
        <input type="text" name="address" value="{{ old('address', $organization->address ?? '') }}" class="field">
    </div>
    <div class="sm:col-span-2">
        <label class="label">Notes internes</label>
        <textarea name="notes" rows="3" class="field" placeholder="Visibles uniquement par l'équipe {{ config('saas.platform_name') }}">{{ old('notes', $organization->notes ?? '') }}</textarea>
    </div>
</div>
