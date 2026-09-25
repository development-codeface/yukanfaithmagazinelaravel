@extends('layouts.admin')

@section('content')

<div class="card w_90">

    <div class="card-header">
        <h5>Edit Article</h5>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('admin.articles.update', $article) }}"
              enctype="multipart/form-data">

            @csrf
            @method('PUT')

            {{-- TITLE --}}
            <div class="form-group">
                <label class="required">Title</label>

                <input type="text"
                       name="title"
                       class="form-control"
                       value="{{ old('title', $article->title) }}"
                       required>
            </div>

            {{-- SUMMARY --}}
            <div class="form-group">
                <label>Post Description</label>

                <textarea name="summary"
                          class="form-control"
                          rows="3">{{ old('summary', $article->summary) }}</textarea>
            </div>

            {{-- CATEGORY --}}
            <div class="form-group">

                <label class="required">Category</label>

                <select name="categories[]"
                        id="article-categories"
                        class="form-control select2 article-multi-select"
                        multiple
                        required>

                    @foreach($categories as $id => $name)

                        <option value="{{ $id }}"
                            {{ collect(old('categories', $article->categories->pluck('id')->all()))->contains($id) ? 'selected' : '' }}>

                            {{ $name }}

                        </option>

                    @endforeach

                </select>

            </div>

            {{-- ARTICLE TYPE --}}
            <div class="form-group">

                <label class="required">Article Type</label>

                <select name="access_type"
                        class="form-control"
                        required>

                    <option value="free"
                        {{ old('access_type', $article->access_type ?? 'free') === 'free' ? 'selected' : '' }}>

                        Free

                    </option>

                    <option value="paid"
                        {{ old('access_type', $article->access_type ?? 'free') === 'paid' ? 'selected' : '' }}>

                        Paid

                    </option>

                </select>

            </div>

            <div class="form-group">

                <label>Single Article Price</label>

                <input type="number"
                       step="0.01"
                       min="0"
                       name="single_article_price"
                       class="form-control"
                       value="{{ old('single_article_price', $article->single_article_price) }}"
                       placeholder="Optional price for buying only this article">

            </div>

            {{-- AUTHOR --}}
            <div class="form-group">

                <label class="required">Author</label>

                <select name="author_id"
                        class="form-control"
                        required>

                    @foreach($authors as $id => $name)

                        <option value="{{ $id }}"
                            {{ $article->author_id == $id ? 'selected' : '' }}>

                            {{ $name }}

                        </option>

                    @endforeach

                </select>

            </div>

            {{-- LEFT SIDE FIXED IMAGE --}}
            <div class="form-group">

                <label>Left Side Fixed Image</label>

                <div id="left-side-fixed-image-preview" class="mb-2" @if(!$article->featured_image_url) style="display:none" @endif>

                    <img id="left-side-fixed-img-tag"
                         src="{{ $article->featured_image_url ? asset($article->featured_image_url) : '' }}"
                         width="200">

                    @if($article->featured_image_url)
                    <div class="mt-1">
                        <label class="text-danger">
                            <input type="checkbox" name="remove_left_side_fixed_image" value="1">
                            Remove left side fixed image
                        </label>
                    </div>
                    @endif

                </div>

                <input type="file"
                       name="left_side_fixed_image"
                       class="form-control"
                       id="left_side_fixed_image_input">

            </div>

            {{-- FEATURE IMAGE --}}
            <div class="form-group">

                <label>Featured Image</label>

                <div id="article-featured-image-preview" class="mb-2" @if(!$article->article_featured_image_url) style="display:none" @endif>

                    <img id="article-featured-img-tag"
                         src="{{ $article->article_featured_image_url ? asset($article->article_featured_image_url) : '' }}"
                         width="200">

                    @if($article->article_featured_image_url)
                    <div class="mt-1">
                        <label class="text-danger">
                            <input type="checkbox" name="remove_article_featured_image" value="1">
                            Remove featured image
                        </label>
                    </div>
                    @endif

                </div>

                <input type="file"
                       name="article_featured_image"
                       class="form-control"
                       id="article_featured_image_input">

            </div>

            {{-- LEFT IMAGE TITLE --}}
            <div class="form-group">

                <label>Left Image Title</label>

                <input type="text"
                       name="left_image_title"
                       class="form-control"
                       value="{{ old('left_image_title', $article->left_image_title) }}">

            </div>

            {{-- BUY BUTTON LINK --}}
            <div class="form-group">

                <label>Buy Button Link</label>

                <input type="text"
                       name="buy_button_link"
                       class="form-control"
                       value="{{ old('buy_button_link', $article->buy_button_link) }}">

            </div>

            <hr>

            {{-- GALLERY --}}
            <h5>Gallery Images</h5>

            <div class="row">

                @foreach($article->galleryImages as $image)

                    <div class="col-md-3 mb-3">

                        <img src="{{ asset($image->image_path) }}"
                             class="img-fluid mb-2">

                        <button type="submit"
                                form="delete-gallery-{{ $image->id }}"
                                class="btn btn-primary btn-sm">
                            <i class="fa fa-trash"></i> Remove
                        </button>

                    </div>

                @endforeach

            </div>

            <div id="gallery-container"></div>

            <button type="button"
                    class="btn btn-primary mt-2"
                    id="addGalleryRow">

                + Add Image

            </button>

            <hr>

            {{-- CONTENT BLOCKS --}}
            <h5>Article Content</h5>

            <div id="blocks-container">

                @foreach($article->blocks as $index => $block)

                    @php
                        $data = $block->block_data;
                    @endphp

                    <div class="card mt-3 block-row">

                        <div class="card-header d-flex justify-content-between align-items-center">

                            <strong>Content Row</strong>

                            <button type="button"
                                    class="btn btn-primary btn-sm remove-row">
                                <i class="fa fa-trash"></i> Remove Row
                            </button>

                        </div>

                        <div class="card-body">

                            <div class="row">

                                {{-- TEXT --}}
                                <div class="col-md-8">

                                    <textarea
                                        id="editor_existing_{{ $index }}"
                                        name="blocks[{{ $index }}][data][content]"
                                        class="form-control editor"
                                        rows="6">{{ $data['content'] ?? '' }}</textarea>

                                </div>

                                {{-- IMAGE --}}
                                <div class="col-md-4">

                                    <div class="block-img-preview mb-2"
                                         @if(empty($data['image'])) style="display:none" @endif>

                                        <img src="{{ !empty($data['image']) ? asset($data['image']) : '' }}"
                                             class="block-img-tag img-fluid mb-2"
                                             width="150">

                                        @if(!empty($data['image']))

                                        <button type="button"
                                                class="btn btn-primary btn-sm remove-image-btn">
                                            <i class="fa fa-trash"></i> Remove Image
                                        </button>

                                        <input type="hidden"
                                               name="blocks[{{ $index }}][data][remove_image]"
                                               class="remove-image-input"
                                               value="0">

                                        @endif

                                    </div>

                                    @if(!empty($data['image']))
                                    <input type="hidden"
                                           name="blocks[{{ $index }}][data][existing_image]"
                                           value="{{ $data['image'] }}">
                                    @endif

                                    <input type="file"
                                           name="blocks[{{ $index }}][data][image]"
                                           class="form-control block-img-input">

                                    <input type="text"
                                           name="blocks[{{ $index }}][data][caption]"
                                           class="form-control mt-2"
                                           value="{{ $data['caption'] ?? '' }}"
                                           placeholder="Image caption">

                                </div>

                            </div>

                            <input type="hidden"
                                   name="blocks[{{ $index }}][type]"
                                   value="text_image">

                        </div>

                    </div>

                @endforeach

            </div>

            {{-- ADD ROW --}}
            <button type="button"
                    class="btn btn-primary mt-3"
                    id="addRow">

                + Add Row

            </button>

            {{-- STATUS --}}
            <div class="form-group mt-4">

                <label>Status</label>

                <select name="status"
                        class="form-control">

                    <option value="draft"
                        {{ $article->status == 'draft' ? 'selected' : '' }}>

                        Draft

                    </option>

                    <option value="published"
                        {{ $article->status == 'published' ? 'selected' : '' }}>

                        Published

                    </option>

                </select>

            </div>

            <button class="btn btn-success mt-3">
                Update Article
            </button>

        </form>

        @foreach($article->galleryImages as $image)

            <form id="delete-gallery-{{ $image->id }}"
                  method="POST"
                  action="{{ route('admin.gallery.delete', $image->id) }}"
                  onsubmit="return confirm('Remove this gallery image?')">

                @csrf
                @method('DELETE')

            </form>

        @endforeach

    </div>

</div>

{{-- ================= ROW TEMPLATE ================= --}}
<template id="row-template">

    <div class="card mt-3 block-row">

        <div class="card-header d-flex justify-content-between align-items-center">

            <strong>Content Row</strong>

            <button type="button"
                    class="btn btn-primary btn-sm remove-row">
                <i class="fa fa-trash"></i> Remove Row
            </button>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-8">

                    <textarea
                        id="editor___INDEX__"
                        name="blocks[__INDEX__][data][content]"
                        class="form-control editor"
                        rows="6"></textarea>

                </div>

                <div class="col-md-4">

                    <div class="block-img-preview mb-2" style="display:none">

                        <img src=""
                             class="block-img-tag img-fluid mb-2"
                             width="150">

                        <button type="button"
                                class="btn btn-primary btn-sm remove-image-btn">
                            <i class="fa fa-trash"></i> Remove Image
                        </button>

                        <input type="hidden"
                               name="blocks[__INDEX__][data][remove_image]"
                               class="remove-image-input"
                               value="0">

                    </div>

                    <input type="file"
                           name="blocks[__INDEX__][data][image]"
                           class="form-control block-img-input">

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

{{-- ================= GALLERY TEMPLATE ================= --}}
<template id="gallery-template">

    <div class="gallery-row mt-2">

        <input type="file"
               name="gallery_images[]"
               class="form-control">

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

document.addEventListener('DOMContentLoaded', function () {

    // ================= LEFT SIDE FIXED IMAGE PREVIEW =================
    const imgInput = document.getElementById('left_side_fixed_image_input');

    if (imgInput) {

        imgInput.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) return;

            const preview = document.getElementById('left-side-fixed-image-preview');

            const img = document.getElementById('left-side-fixed-img-tag');

            img.src = URL.createObjectURL(file);

            preview.style.display = '';

            const removeChk = preview.querySelector('input[name="remove_left_side_fixed_image"]');

            if (removeChk) {
                removeChk.checked = false;
            }

        });

    }

    // ================= FEATURED IMAGE PREVIEW =================
    const featuredImgInput = document.getElementById('article_featured_image_input');

    if (featuredImgInput) {

        featuredImgInput.addEventListener('change', function () {

            const file = this.files[0];

            if (!file) return;

            const preview = document.getElementById('article-featured-image-preview');

            const img = document.getElementById('article-featured-img-tag');

            img.src = URL.createObjectURL(file);

            preview.style.display = '';

            const removeChk = preview.querySelector('input[name="remove_article_featured_image"]');

            if (removeChk) {
                removeChk.checked = false;
            }

        });

    }

    // ================= SELECT2 =================
    if (window.jQuery && $.fn.select2) {

        $('#article-categories').select2({
            placeholder: 'Select categories',
            width: '100%',
            closeOnSelect: false
        });

    }

    // ================= INDEX =================
    let blockIndex = {{ $article->blocks->count() }};

    // ================= INIT EXISTING EDITORS =================
    document.querySelectorAll('.editor').forEach(function (el) {

        if (!el.id) {
            el.id = 'editor_existing_' + Math.floor(Math.random() * 10000);
        }

        if (CKEDITOR.instances[el.id]) {
            CKEDITOR.instances[el.id].destroy(true);
        }

        CKEDITOR.replace(el.id, {
            height: 300,
            removePlugins: 'exportpdf'
        });

    });

    // ================= ADD ROW =================
    const addRowBtn = document.getElementById('addRow');

    if (addRowBtn) {

        addRowBtn.addEventListener('click', function () {

            let template = document
                .getElementById('row-template')
                .innerHTML;

            template = template.replace(/__INDEX__/g, blockIndex);

            document
                .getElementById('blocks-container')
                .insertAdjacentHTML('beforeend', template);

            let editorId = 'editor_' + blockIndex;

            setTimeout(function () {

                if (CKEDITOR.instances[editorId]) {
                    CKEDITOR.instances[editorId].destroy(true);
                }

                CKEDITOR.replace(editorId, {
                    height: 300,
                    removePlugins: 'exportpdf'
                });

            }, 100);

            blockIndex++;

        });

    }

    // ================= REMOVE ROW =================
    document.addEventListener('click', function (e) {

        if (e.target.classList.contains('remove-row')) {

            const row = e.target.closest('.block-row');

            const textarea = row.querySelector('textarea');

            if (textarea && CKEDITOR.instances[textarea.id]) {

                CKEDITOR.instances[textarea.id].destroy(true);

            }

            row.remove();

        }

    });

    // ================= BLOCK IMAGE PREVIEW =================
    document.addEventListener('change', function (e) {

        if (e.target.classList.contains('block-img-input')) {

            const file = e.target.files[0];

            if (!file) return;

            const col = e.target.closest('.col-md-4');

            const preview = col.querySelector('.block-img-preview');

            const img = col.querySelector('.block-img-tag');

            img.src = URL.createObjectURL(file);

            preview.style.display = '';

            const removeInput = col.querySelector('.remove-image-input');

            if (removeInput) {
                removeInput.value = 0;
            }

        }

    });

    // ================= REMOVE BLOCK IMAGE =================
    document.addEventListener('click', function (e) {

        if (e.target.classList.contains('remove-image-btn')) {

            const preview = e.target.closest('.block-img-preview');

            const img = preview.querySelector('.block-img-tag');

            const hiddenInput = preview.querySelector('.remove-image-input');

            if (hiddenInput) {
                hiddenInput.value = 1;
            }

            img.src = '';

            preview.style.display = 'none';

            const fileInput = preview.closest('.col-md-4')
                .querySelector('.block-img-input');

            if (fileInput) {
                fileInput.value = '';
            }

        }

    });

    // ================= ADD GALLERY =================
    const addGalleryBtn = document.getElementById('addGalleryRow');

    if (addGalleryBtn) {

        addGalleryBtn.addEventListener('click', function () {

            const tpl = document
                .getElementById('gallery-template')
                .innerHTML;

            document
                .getElementById('gallery-container')
                .insertAdjacentHTML('beforeend', tpl);

        });

    }

    // ================= FORM SUBMIT =================
    const form = document.querySelector('form');

    if (form) {

        form.addEventListener('submit', function () {

            for (let instance in CKEDITOR.instances) {

                CKEDITOR.instances[instance].updateElement();

            }

        });

    }

});

</script>

@endsection
