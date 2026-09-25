@extends('layouts.admin')
@section('content')

<div class="card w_80">
    <div class="card-header">
        <p>
            <i class="fi fi-br-edit mr_15_icc"></i>
            Create Magazine Issue
        </p>
    </div>

    <div class="card-body">
        <form method="POST"
              action="{{ route('admin.magazine-issues.store') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- Title --}}
            <div class="form-group">
                <label class="required">Title</label>
                <input type="text"
                       name="title"
                       class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                       value="{{ old('title') }}"
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
                       value="{{ old('issue_date') }}">
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"
                          class="form-control"
                          rows="4">{{ old('description') }}</textarea>
            </div>

            {{-- PDF URL --}}
            <div class="form-group">
                <label>PDF URL</label>
                <input type="text"
                       name="pdf_url"
                       class="form-control"
                       value="{{ old('pdf_url') }}">
            </div>

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