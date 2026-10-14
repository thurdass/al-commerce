<div class="form-grid">
    <div class="field-wide"><x-input name="name" label="Nome do produto" :value="old('name', $product?->name)" maxlength="255" required autofocus /></div>
    <x-select name="category_id" label="Categoria (opcional)">
        <option value="">Sem categoria</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </x-select>
    <x-select name="availability_status" label="Disponibilidade" required>
        @foreach (\App\ProductAvailabilityStatus::cases() as $availability)
            <option value="{{ $availability->value }}" @selected(old('availability_status', $product?->availability_status->value ?? \App\ProductAvailabilityStatus::Available->value) === $availability->value)>{{ $availability->label() }}</option>
        @endforeach
    </x-select>
    <x-field name="description" label="Descrição" class="field-wide">
        <textarea id="description" name="description" rows="5" maxlength="5000" required @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $product?->description) }}</textarea>
    </x-field>
    <x-input name="price" label="Preço (R$)" type="number" min="0" max="99999999.99" step="0.01" :value="old('price', $product?->price)" required />
    <x-input name="stock_quantity" label="Quantidade em estoque (opcional)" type="number" min="0" max="4294967295" step="1" :value="old('stock_quantity', $product?->stock_quantity)" />
    <div class="field-wide">
        <input type="hidden" name="active" value="0">
        <label for="active" class="check-field">
            <input id="active" type="checkbox" name="active" value="1" @checked(old('active', $product?->active ?? true))>
            Produto ativo
        </label>
        <p class="field-hint">Estoque e disponibilidade são informados separadamente. Desativar preserva os dados do produto.</p>
    </div>
</div>
