@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">Apparel Banners <a class="btn btn-success float-right" href="{{ route('admin.apparel-banners.create') }}">Add Apparel Banner</a></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Image</th><th>Title</th><th>Link</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($ads as $ad)
                        <tr>
                            <td><img src="{{ asset($ad->image) }}" alt="{{ $ad->title ?: 'Advertisement' }}" style="max-width:160px;max-height:80px;"></td>
                            <td>{{ $ad->title ?: '-' }}</td>
                            <td><a href="{{ $ad->link }}" target="_blank" rel="noopener noreferrer">{{ $ad->link }}</a></td>
                            <td>{{ $ad->status ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a class="btn btn-sm btn-info" href="{{ route('admin.apparel-banners.edit', $ad) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.apparel-banners.destroy', $ad) }}" class="d-inline" onsubmit="return confirm('Delete this advertisement?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No apparel banners added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $ads->links() }}
    </div>
</div>
@endsection
