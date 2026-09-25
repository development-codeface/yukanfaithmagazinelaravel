@extends('layouts.admin')

@section('content')
<div class="card w_90">
    <div class="card-header">
        <h5>Create Article</h5>
    </div>

    <div class="card-body">
        <form method="POST"
              action="{{ route('admin.articles.store') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- Title --}}
            <div class="form-group">
                <label class="required">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
            </div>

            
            {{-- Summary --}}
            <div class="form-group">
                <label>Post Description</label>
                <textarea name="summary" class="form-control" rows="3">{{ old('summary') }}</textarea>
            </div>

            {{-- Category --}}
            <div class="form-group">
                <label class="required">Category</label>
               <select name="categories[]" id="article-categories" multiple class="form-control select2 article-multi-select" required>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" {{ collect(old('categories', []))->contains($id) ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Access Type --}}
            <div class="form-group">
                <label class="required">Article Type</label>
                <select name="access_type" class="form-control" required>
                    <option value="free" {{ old('access_type', 'free') === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="paid" {{ old('access_type') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>

            <div class="form-group">
                <label>Single Article Price</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="single_article_price"
                       class="form-control"
                       value="{{ old('single_article_price') }}"
                       placeholder="Optional price for buying only this article">
            </div>

            {{-- Author --}}
            <div class="form-group">
                <label class="required">Author</label>
                <select name="author_id" class="form-control" required>
                    @foreach($authors as $id => $name)
                        <option value="{{ $id }}" {{ old('author_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Left Side Fixed Image</label>
                <input type="file" name="left_side_fixed_image" class="form-control">
            </div>

            <div class="form-group">
                <label>Featured Image</label>
                <input type="file" name="article_featured_image" class="form-control">
            </div>

            
            <div class="form-group">
                <label>Left Image Title</label>
                <input type="text" name="left_image_title"
                    class="form-control"
                    value="{{ old('left_image_title') }}">
            </div>



            <div class="form-group">
                <label>Buy Button Link</label>
                <input type="text" name="buy_button_link"
                    class="form-control"
                    value="{{ old('buy_button_link') }}">
            </div>

            <hr>
                <h5>Gallery Images</h5>

                <div id="gallery-container"></div>

                <button type="button" class="btn btn-secondary mt-2" id="addGalleryRow">
                    Add Image
                </button>


            <!-- <div class="form-group">
                <label>PDF Upload</label>
                <input type="file" name="pdf_file" class="form-control">
            </div> -->

            <hr>
            <h5>Article Content</h5>

            {{-- BLOCKS --}}
            <div id="blocks-container" style="margin-left:5px"></div>

            <button type="button" class="btn btn-primary mt-3" id="addRow">
                + Add Row
            </button>

            {{-- Status --}}
            <div class="form-group mt-4">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <button class="btn btn-success mt-3">Save Article</button>
        </form>
    </div>
</div>

{{-- ================= ROW TEMPLATE ================= --}}
<template id="row-template">
    <div class="card mt-3 block-row" data-index="__INDEX__">
        <div class="card-header d-flex justify-content-between">
            <strong>Content Row</strong>
            <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
        </div>

        <div class="card-body">
            <div class="row" style="margin-left:-70px">
                <div class="col-md-8">
                    <textarea id="editor___INDEX__"
                        name="blocks[__INDEX__][data][content]"
                        class="form-control editor"
                        rows="6"></textarea>
                </div>

                <div class="col-md-4">
                    <input type="file"
                           name="blocks[__INDEX__][data][image]"
                           class="form-control">

                    <input type="text"
                           name="blocks[__INDEX__][data][caption]"
                           class="form-control mt-2"
                           placeholder="Image caption">
                </div>
            </div>

            <input type="hidden"
                   name="blocks[__INDEX__][type]"
                   value="text_image">
        </div>
    </div>
</template>

<template id="gallery-template">
    <div class="gallery-row mt-2">
        <input type="file" name="gallery_images[]" class="form-control">
    </div>
</template>


@endsection

@section('styles')
<style>
    .article-multi-select + .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 52px;
        border: 1px solid #d8dbe0;
        border-radius: 8px;
        padding: 7px 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #9f2d28;
        box-shadow: 0 0 0 0.2rem rgba(159, 45, 40, 0.12);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #9f2d28;
        border: 0;
        border-radius: 6px;
        color: #fff;
        padding: 4px 9px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-right: 6px;
    }
</style>
@endsection

@section('scripts')

<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>

<script>
(function () {

    if (window.jQuery && $.fn.select2) {
        $('#article-categories').select2({
            placeholder: 'Select categories',
            width: '100%',
            closeOnSelect: false
        });
    }

    let blockIndex = 0;

    document.getElementById('addRow').addEventListener('click', function () {

        let template = document.getElementById('row-template').innerHTML;
        template = template.split('__INDEX__').join(blockIndex);

        document.getElementById('blocks-container')
            .insertAdjacentHTML('beforeend', template);

        let editorId = 'editor_' + blockIndex;

        if (CKEDITOR.instances[editorId]) {
            CKEDITOR.instances[editorId].destroy(true);
        }

        CKEDITOR.replace(editorId, {
            height: 350,
            filebrowserUploadUrl: "{{ route('admin.article.image.upload') }}?_token={{ csrf_token() }}",
            filebrowserUploadMethod: 'form'
        });

        blockIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.block-row').remove();
        }
    });

    document.querySelector('form').addEventListener('submit', function () {
        for (let instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
    });

// ================= GALLERY ADD =================
    const addGalleryBtn = document.getElementById('addGalleryRow');

    if (addGalleryBtn) {
        addGalleryBtn.addEventListener('click', function () {

            let tpl = document.getElementById('gallery-template').innerHTML;

            document.getElementById('gallery-container')
                .insertAdjacentHTML('beforeend', tpl);
        });
    }


})();
</script>
@endsection
