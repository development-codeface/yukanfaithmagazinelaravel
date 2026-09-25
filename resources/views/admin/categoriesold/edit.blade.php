@extends('layouts.admin')
@section('content')

<div class="card w_80">
    <div class="card-header">
        <p><i class="fi fi-br-edit mr_15_icc"></i> Edit Category</p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.categories.update', $category->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="required">Category Name</label>
                <input type="text"
                       name="category_name"
                       class="form-control"
                       value="{{ old('category_name', $category->category_name) }}"
                       required>
            </div>

            <button class="btn btn-success min-w-200">
                Update
            </button>
        </form>
    </div>
</div>

@endsection