@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-list mr_15_icc"></i> Apparel Products</p>

        <a class="btn btn-success" href="{{ route('admin.apparel-products.create') }}">
            <i class="fi fi-br-plus-small mr_5"></i>
            Add Product
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success m-3">{{ session('success') }}</div>
    @endif

    <div class="card-body">
        <form method="GET" class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or SKU">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('category_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-dark" type="submit">Filter</button>
                <a href="{{ route('admin.apparel-products.index') }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                @if($product->primaryImage)
                                    <img src="{{ asset($product->primaryImage->image_path) }}" alt="{{ $product->name }}" style="width: 64px; height: 64px; object-fit: cover;">
                                @endif
                            </td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                @if($product->sku)
                                    <div class="text-muted small">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>${{ number_format((float) $product->price, 2) }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>
                                <span class="badge badge-{{ $product->status === 'published' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-xs btn-info" href="{{ route('admin.apparel-products.edit', $product) }}">
                                    <i class="fi fi-br-edit"></i>
                                </a>
                                <form action="{{ route('admin.apparel-products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-danger" type="submit">
                                        <i class="fi fi-br-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
