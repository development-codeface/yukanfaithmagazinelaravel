@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-list mr_15_icc"></i> Category List</p>

        <a class="btn btn-success" href="{{ route('admin.categories.create') }}">
            <i class="fi fi-br-plus-small"></i> Add Category
        </a>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    <td>{{ $category->category_name }}</td>
                    <td>
                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                           class="btn btn-xs btn-info">
                            <i class="fi fi-br-edit"></i>
                        </a>

                        <form action="{{ route('admin.categories.destroy', $category->id) }}"
                              method="POST"
                              style="display:inline-block"
                              onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-xs btn-danger">
                                <i class="fi fi-br-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection