@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">{{ $apparelBanner->exists ? 'Edit Apparel Banner' : 'Add Apparel Banner' }}</div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{ $apparelBanner->exists ? route('admin.apparel-banners.update', $apparelBanner) : route('admin.apparel-banners.store') }}">
            @csrf
            @if($apparelBanner->exists) @method('PUT') @endif
            <div class="form-group">
                <label for="ad-title">Title (optional)</label>
                <input id="ad-title" class="form-control" name="title" maxlength="255" value="{{ old('title', $apparelBanner->title) }}">
            </div>
            <div class="form-group">
                <label for="ad-link">Link (optional)</label>
                <input id="ad-link" type="url" class="form-control" name="link" maxlength="2048" value="{{ old('link', $apparelBanner->link) }}" placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label for="ad-image">Banner image</label>
                <input id="ad-image" type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp" @required(!$apparelBanner->exists)>
                <small>JPG, PNG or WebP, maximum 4 MB. Use a wide banner image.</small>
                @if($apparelBanner->image)<div class="mt-2"><img src="{{ asset($apparelBanner->image) }}" alt="Current banner" style="max-width:100%;max-height:180px;"></div>@endif
            </div>
            <div class="form-group">
                <label for="ad-status">Status</label>
                <select id="ad-status" name="status" class="form-control">
                    <option value="1" @selected((string) old('status', (int) $apparelBanner->status) === '1')>Active</option>
                    <option value="0" @selected((string) old('status', (int) $apparelBanner->status) === '0')>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sort-order">Display order</label>
                <input id="sort-order" type="number" name="sort_order" class="form-control" min="0" max="99999" value="{{ old('sort_order', $apparelBanner->sort_order ?? 0) }}" required>
            </div>
            <button class="btn btn-success" type="submit">Save</button>
            <a class="btn btn-secondary" href="{{ route('admin.apparel-banners.index') }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
