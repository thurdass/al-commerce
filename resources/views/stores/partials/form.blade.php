<div class="form-grid">
    <div class="field-wide"><x-input name="name" label="Nome da loja" :value="old('name', $store?->name)" maxlength="255" required autofocus /></div>
    <x-field name="description" label="Descrição" class="field-wide">
        <textarea id="description" name="description" rows="5" maxlength="5000" required @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $store?->description) }}</textarea>
    </x-field>
    <div class="field-wide"><x-input name="whatsapp" label="WhatsApp" type="tel" :value="old('whatsapp', $store?->whatsapp)" inputmode="numeric" pattern="[1-9][0-9]{9,14}" maxlength="15" hint="Informe apenas números, com código do país e DDD. Exemplo: 5571999999999." required /></div>
    <x-input name="city" label="Cidade" :value="old('city', $store?->city)" maxlength="255" autocomplete="address-level2" required />
    <x-input name="state" label="Estado (UF)" :value="old('state', $store?->state)" maxlength="2" autocomplete="address-level1" placeholder="BA" required />
    <div class="field-wide"><x-input name="address" label="Endereço (opcional)" :value="old('address', $store?->address)" maxlength="255" autocomplete="street-address" /></div>
    <div class="field-wide">
        <input type="hidden" name="active" value="0">
        <label for="active" class="check-field">
            <input id="active" type="checkbox" name="active" value="1" @checked(old('active', $store?->active ?? true))>
            Loja ativa
        </label>
    </div>
</div>
