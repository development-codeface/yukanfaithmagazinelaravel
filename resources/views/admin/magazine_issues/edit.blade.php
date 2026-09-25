@extends('layouts.admin')
@section('content')

<div class="card w_80">
    <div class="card-header">
        <p>
            <i class="fi fi-br-edit mr_15_icc"></i>
            Edit Magazine Issue
        </p>
    </div>

    <div class="card-body">
        <form method="POST"
              action="{{ route('admin.magazine-issues.update', $magazineIssue->id) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Title --}}
            <div class="form-group">
                <label class="required">Title</label>
                <input type="text"
                       name="title"
                       class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                       value="{{ old('title', $magazineIssue->title) }}"
                       required>
                @if($errors->has('title'))
                    <div class="invalid-feedback">{{ $errors->first('title') }}</div>
                @endif
            </div>

            {{-- Issue Date --}}
            <div class="form-group">
                <label>Issue Date</label>
                <input type="date"
                       name="issue_date"
                       class="form-control"
                       value="{{ old('issue_date', $magazineIssue->issue_date) }}">
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"
                          class="form-control"
                          rows="4">{{ old('description', $magazineIssue->description) }}</textarea>
            </div>

            {{-- PDF URL --}}
            <div class="form-group">
                <label>PDF URL</label>
                <input type="text"
                       name="pdf_url"
                       class="form-control"
                       value="{{ old('pdf_url', $magazineIssue->pdf_url) }}">
            </div>

            {{-- Published At (read-only) --}}
            @if($magazineIssue->published_at)
                <div class="form-group">
                    <label>Published At</label>
                    <input type="text"
                           class="form-control"
                           value="{{ $magazineIssue->published_at }}"
                           readonly>
                </div>
            @endif

            {{-- Submit --}}
            <div class="form-group">
                <button class="btn btn-success min-w-200" type="submit">
                    {{ trans('global.save') ?? 'Save' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection