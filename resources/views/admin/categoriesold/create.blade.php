@extends('layouts.admin')
@section('content')

<div class="card w_80">
    <div class="card-header">
        <p><i class="fi fi-br-plus mr_15_icc"></i> Create Category</p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf

            <div class="form-group">
                <label class="required">Category Name</label>
                <input type="text"
                       name="category_name"
                       class="form-control {{ $errors->has('category_name') ? 'is-invalid' : '' }}"
                       value="{{ old('category_name') }}"
                       required>

                @if($errors->has('category_name'))
                    <div class="invalid-feedback">
                        {{ $errors->first('category_name') }}
                    </div>
                @endif
            </div>

            <button class="btn btn-success min-w-200">
                Save
            </button>
        </form>
    </div>
</div>

@endsection