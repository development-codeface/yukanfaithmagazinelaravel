@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-list"></i> Articles</p>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-success">
            <i class="fi fi-br-plus"></i> Add Article
        </a>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="mb-4">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text"
                           name="search"
                           class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Search title or summary">
                </div>

                <div class="col-md-2 mb-2">
                    <select name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="author_id" class="form-control">
                        <option value="">All Authors</option>
                        @foreach($authors as $id => $name)
                            <option value="{{ $id }}" {{ request('author_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="access_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="free" {{ request('access_type') === 'free' ? 'selected' : '' }}>Free</option>
                        <option value="paid" {{ request('access_type') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>

                <div class="col-md-1 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary mr-1">
                        Filter
                    </button>
                    <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>{{ $article->id }}</td>
                    <td>{{ $article->title }}</td>
                    <td>
                        {{ $article->categories->pluck('category_name')->join(', ') ?: ($article->category->category_name ?? '-') }}
                    </td>
                    <td>{{ $article->author->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ ($article->access_type ?? 'free') === 'paid' ? 'badge-warning' : 'badge-secondary' }}">
                            {{ ucfirst($article->access_type ?? 'free') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $article->status }}</span>
                    </td>
                    <td>
                        <a class="btn btn-xs btn-info"
                           href="{{ route('admin.articles.edit', $article) }}">
                            <i class="fi fi-br-edit"></i>
                        </a>

                        <form method="POST"
                              action="{{ route('admin.articles.destroy', $article) }}"
                              style="display:inline"
                              onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-xs btn-danger">
                                <i class="fi fi-br-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No articles found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $articles->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

@endsection
