<div class="form-group">
    <label class="required" for="name">Name</label>
    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" value="{{ old('name', $category->name ?? '') }}" list="apparel-category-options" required>
    <datalist id="apparel-category-options">
        <option value="Women">
        <option value="Men">
        <option value="Accessories">
        <option value="Home & Living">
    </datalist>
    @if($errors->has('name'))
        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
    @endif
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" name="description" id="description" rows="4">{{ old('description', $category->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="image">Image</label>
    @if(!empty($category?->image))
        <div class="mb-2">
            <img src="{{ asset($category->image) }}" alt="{{ $category->name }}" style="width: 100px; height: 100px; object-fit: cover;">
        </div>
    @endif
    <input class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" type="file" name="image" id="image" accept="image/*">
</div>

<div class="form-group">
    <label for="sort_order">Sort Order</label>
    <input class="form-control {{ $errors->has('sort_order') ? 'is-invalid' : '' }}" type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
</div>

<div class="form-group form-check">
    <input type="checkbox" class="form-check-input" name="status" id="status" value="1" {{ old('status', $category->status ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="status">Active</label>
</div>

<div class="form-group">
    <button class="btn btn-success min-w-200" type="submit">Save</button>
    <a href="{{ route('admin.apparel-categories.index') }}" class="btn btn-secondary">Cancel</a>
</div>
