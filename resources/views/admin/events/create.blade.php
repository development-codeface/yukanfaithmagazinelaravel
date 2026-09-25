@extends('layouts.admin')

@section('content')
<div class="card">

    <div class="card-header">
        <p>
            <i class="fi fi-br-edit mr_15_icc"></i>
            Create Event
        </p>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('admin.events.store') }}"
              enctype="multipart/form-data">

            @csrf

            {{-- TITLE --}}
            <div class="form-group">
                <label class="required">Title</label>

                <input type="text"
                       name="title"
                       class="form-control"
                       value="{{ old('title') }}"
                       required>
            </div>


            {{-- SUBTITLE --}}
            <div class="form-group">
                <label>Subtitle</label>

                <input type="text"
                       name="subtitle"
                       class="form-control"
                       value="{{ old('subtitle') }}">
            </div>


            {{-- DESCRIPTION --}}
            <div class="form-group">
                <label>Description</label>

                <textarea name="description"
                          id="editor"
                          class="form-control"
                          rows="5">{{ old('description') }}</textarea>
            </div>


            {{-- IMAGE --}}
            <div class="form-group">
                <label>Image</label>

                <input type="file"
                       name="image"
                       class="form-control">
            </div>


            {{-- BUTTON LINK --}}
            <div class="form-group">
                <label>Button Link</label>

                <input type="text"
                       name="button_link"
                       class="form-control"
                       placeholder="https://example.com"
                       value="{{ old('button_link') }}">
                <small class="text-muted">Enter a valid URL (e.g., https://example.com)</small>
            </div>

            {{-- STATUS --}}
            <div class="form-group">
                <label>Status</label>
                <div class="form-check">
                    <input type="checkbox"
                           name="status"
                           value="1"
                           class="form-check-input"
                           id="status"
                           {{ old('status', 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">
                        Active
                    </label>
                </div>
                <small class="text-muted">Check to make this event visible on the frontend</small>
            </div>

            <div class="form-group mt-3">
                <button class="btn btn-success">
                    Save Event
                </button>
            </div>

        </form>

    </div>
</div>
@endsection


@section('scripts')
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>

<script>
CKEDITOR.replace('editor',{
    height:300
});
</script>
@endsection
