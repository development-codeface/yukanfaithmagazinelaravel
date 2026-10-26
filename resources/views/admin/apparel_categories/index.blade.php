@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-list mr_15_icc"></i> Apparel Categories</p>

        <a class="btn btn-success" href="{{ route('admin.apparel-categories.create') }}">
            <i class="fi fi-br-plus-small mr_5"></i>
            Add Category
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success m-3">{{ session('success') }}</div>
    @endif

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>
                                @if($category->image)
                                    <img src="{{ asset($category->image) }}" alt="{{ $category->name }}" style="width: 58px; height: 58px; object-fit: cover;">
                                @endif
                            </td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>
                                <span class="badge badge-{{ $category->status ? 'success' : 'secondary' }}">
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-xs btn-info" href="{{ route('admin.apparel-categories.edit', $category) }}">
                                    <i class="fi fi-br-edit"></i>
                                </a>
                                <form action="{{ route('admin.apparel-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                            <td colspan="6" class="text-center">No apparel categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
