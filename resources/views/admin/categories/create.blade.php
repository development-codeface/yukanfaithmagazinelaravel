@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <p><i class="fi fi-br-plus mr_15_icc"></i> Create Category</p>
    </div>

```
<div class="card-body">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row">

            <!-- LEFT -->
            <div class="col-md-8">

                <div class="form-group">
                    <label for="category_name" class="form-label required">
                        Category Name
                    </label>

                    <input type="text"
                           id="category_name"
                           name="category_name"
                           value="{{ old('category_name') }}"
                           class="form-control {{ $errors->has('category_name') ? 'is-invalid' : '' }}">

                    @error('category_name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        Description
                    </label>

                    <textarea id="description"
                              name="description"
                              rows="4"
                              class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}">{{ old('description') }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="category_subtitle" class="form-label">
                        Category Subtitle
                    </label>

                    <textarea id="category_subtitle"
                              name="category_subtitle"
                              rows="2"
                              class="form-control {{ $errors->has('category_subtitle') ? 'is-invalid' : '' }}">{{ old('category_subtitle') }}</textarea>

                    @error('category_subtitle')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="subtitle_description" class="form-label">
                        Subtitle Description
                    </label>

                    <textarea id="subtitle_description"
                              name="subtitle_description"
                              rows="4"
                              class="form-control {{ $errors->has('subtitle_description') ? 'is-invalid' : '' }}">{{ old('subtitle_description') }}</textarea>

                    @error('subtitle_description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

            <!-- RIGHT -->
            <div class="col-md-4">

                <div class="form-group">
                    <label class="form-label fw-bold required">
                        Banner Image
                    </label>

                    <input type="file"
                           name="banner_image"
                           id="bannerImageInput"
                           class="d-none {{ $errors->has('banner_image') ? 'is-invalid' : '' }}"
                           accept="image/*"
                           onchange="showBannerPreview(this)">

                    <div class="border rounded p-3 text-center bg-light"
                         style="cursor:pointer"
                         onclick="document.getElementById('bannerImageInput').click()">

                        <p id="bannerPlaceholder" class="text-muted mb-2">
                            No image selected
                        </p>

                        <button type="button" class="btn btn-outline-primary btn-sm">
                            Add Image
                        </button>
                    </div>

                    <img id="bannerPreview"
                         class="img-fluid mt-2 d-none"
                         style="max-height:200px;border:1px solid #ddd;">

                    @error('banner_image')
                        <div class="text-danger mt-1">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

        </div>

        <hr>

        <h5>Banner Slider</h5>

        <table class="table table-bordered" id="sliderTable">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Link</th>
                    <th width="120">Action</th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td>
                        <input type="file"
                               name="slider_image[]"
                               class="d-none"
                               accept="image/*"
                               onchange="previewRowImage(this)">

                        <div class="border p-2 text-center"
                             style="cursor:pointer"
                             onclick="this.previousElementSibling.click()">
                            <small class="text-muted">Add Image</small>
                        </div>

                        <img class="img-fluid mt-2 d-none"
                             style="max-height:100px;">
                    </td>

                    <td>
                        <input type="text"
                               name="slider_link[]"
                               value="{{ old('slider_link.0') }}"
                               class="form-control"
                               placeholder="https://example.com">
                    </td>

                    <td class="text-center">
                        <button type="button"
                                class="btn btn-danger btn-sm"
                                onclick="removeRow(this)">
                            Remove
                        </button>
                    </td>
                </tr>

            </tbody>
        </table>

        <button type="button"
                class="btn btn-primary mb-3"
                onclick="addSliderRow()">
            + Add Row
        </button>

        <br>

        <button type="submit" class="btn btn-success min-w-200">
            Save Category
        </button>

    </form>
</div>
```

</div>

<script>
    function showBannerPreview(input) {
        const preview = document.getElementById('bannerPreview');
        const placeholder = document.getElementById('bannerPlaceholder');

        if (input.files && input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('d-none');
            placeholder.innerText = input.files[0].name;
        }
    }

    function previewRowImage(input) {
        if (!input.files[0]) return;

        const img = input.nextElementSibling.nextElementSibling;
        img.src = URL.createObjectURL(input.files[0]);
        img.classList.remove('d-none');
    }

    function addSliderRow() {
        const table = document.querySelector('#sliderTable tbody');

        table.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <input type="file"
                           name="slider_image[]"
                           class="d-none"
                           accept="image/*"
                           onchange="previewRowImage(this)">

                    <div class="border p-2 text-center"
                         style="cursor:pointer"
                         onclick="this.previousElementSibling.click()">
                        <small class="text-muted">Add Image</small>
                    </div>

                    <img class="img-fluid mt-2 d-none"
                         style="max-height:100px;">
                </td>

                <td>
                    <input type="text"
                           name="slider_link[]"
                           class="form-control"
                           placeholder="https://example.com">
                </td>

                <td class="text-center">
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            onclick="removeRow(this)">
                        Remove
                    </button>
                </td>
            </tr>
        `);
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('#sliderTable tbody tr');

        if (rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
</script>

@endsection
