@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">{{ $adBanner->exists ? 'Edit Ads Banner' : 'Add Ads Banner' }}</div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{ $adBanner->exists ? route('admin.ad-banners.update', $adBanner) : route('admin.ad-banners.store') }}">
            @csrf
            @if($adBanner->exists) @method('PUT') @endif
            <div class="form-group">
                <label for="ad-title">Title (optional)</label>
                <input id="ad-title" class="form-control" name="title" maxlength="255" value="{{ old('title', $adBanner->title) }}">
            </div>
            <div class="form-group">
                <label for="ad-link">Link</label>
                <input id="ad-link" type="url" class="form-control" name="link" maxlength="2048" value="{{ old('link', $adBanner->link) }}" required placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label for="ad-image">Banner image</label>
                <input id="ad-image" type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp" @required(!$adBanner->exists)>
                <small>JPG, PNG or WebP, maximum 4 MB. Use a wide banner image.</small>
                @if($adBanner->image)<div class="mt-2"><img src="{{ asset($adBanner->image) }}" alt="Current banner" style="max-width:100%;max-height:180px;"></div>@endif
            </div>
            <div class="form-group">
                <label for="ad-status">Status</label>
                <select id="ad-status" name="status" class="form-control">
                    <option value="1" @selected((string) old('status', (int) $adBanner->status) === '1')>Active</option>
                    <option value="0" @selected((string) old('status', (int) $adBanner->status) === '0')>Inactive</option>
                </select>
            </div>
            <button class="btn btn-success" type="submit">Save</button>
            <a class="btn btn-secondary" href="{{ route('admin.ad-banners.index') }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
