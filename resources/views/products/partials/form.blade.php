<label for="name">Nome do produto</label>
<input id="name" name="name" value="{{ old('name', $product?->name) }}" maxlength="255" required autofocus>

<label for="description">Descrição</label>
<textarea id="description" name="description" rows="5" maxlength="5000" required>{{ old('description', $product?->description) }}</textarea>

<label for="price">Preço (R$)</label>
<input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('price', $product?->price) }}" required>

<label for="stock_quantity">Quantidade em estoque (opcional)</label>
<input id="stock_quantity" name="stock_quantity" type="number" min="0" max="4294967295" step="1" value="{{ old('stock_quantity', $product?->stock_quantity) }}">

<label for="availability_status">Disponibilidade</label>
<select id="availability_status" name="availability_status" required>
    @foreach (\App\ProductAvailabilityStatus::cases() as $availability)
        <option value="{{ $availability->value }}" @selected(old('availability_status', $product?->availability_status->value ?? \App\ProductAvailabilityStatus::Available->value) === $availability->value)>{{ $availability->label() }}</option>
    @endforeach
</select>

<input type="hidden" name="active" value="0">
<label for="active">
    <input id="active" type="checkbox" name="active" value="1" @checked(old('active', $product?->active ?? true))>
    Produto ativo
</label>
<p>Estoque e disponibilidade são informados separadamente. Desativar preserva os dados do produto.</p>
