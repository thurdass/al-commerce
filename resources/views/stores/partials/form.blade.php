<label for="name">Nome da loja</label>
<input id="name" name="name" value="{{ old('name', $store?->name) }}" maxlength="255" required autofocus>

<label for="description">Descrição</label>
<textarea id="description" name="description" rows="5" maxlength="5000" required>{{ old('description', $store?->description) }}</textarea>

<label for="whatsapp">WhatsApp</label>
<p id="whatsapp-help">Informe apenas números, com código do país e DDD. Exemplo: 5571999999999.</p>
<input id="whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp', $store?->whatsapp) }}" inputmode="numeric" pattern="[1-9][0-9]{9,14}" maxlength="15" aria-describedby="whatsapp-help" required>

<label for="city">Cidade</label>
<input id="city" name="city" value="{{ old('city', $store?->city) }}" maxlength="255" autocomplete="address-level2" required>

<label for="state">Estado (UF)</label>
<input id="state" name="state" value="{{ old('state', $store?->state) }}" maxlength="2" autocomplete="address-level1" placeholder="BA" required>

<label for="address">Endereço (opcional)</label>
<input id="address" name="address" value="{{ old('address', $store?->address) }}" maxlength="255" autocomplete="street-address">

<input type="hidden" name="active" value="0">
<label for="active">
    <input id="active" type="checkbox" name="active" value="1" @checked(old('active', $store?->active ?? true))>
    Loja ativa
</label>
