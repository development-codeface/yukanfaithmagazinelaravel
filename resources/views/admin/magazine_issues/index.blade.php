@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        <p>
            <i class="fi fi-br-list mr_15_icc"></i>
            Magazine Issues List
        </p>

        <div class="mt-2">
            <a class="btn btn-success"
               href="{{ route('admin.magazine-issues.create') }}">
                <i class="fi fi-br-plus-small mr_5"></i>
                Add Magazine Issue
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Issue Date</th>
                        <th>Published At</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($issues as $issue)
                        <tr>
                            <td>{{ $issue->id }}</td>
                            <td>{{ $issue->title }}</td>
                            <td>{{ $issue->issue_date ?? '-' }}</td>
                            <td>{{ $issue->published_at ?? '-' }}</td>
                            <td>
                                <a class="btn btn-xs btn-info"
                                   href="{{ route('admin.magazine-issues.edit', $issue->id) }}">
                                    <i class="fi fi-br-edit"></i>
                                </a>

                                <form action="{{ route('admin.magazine-issues.destroy', $issue->id) }}"
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
</div>

@endsection