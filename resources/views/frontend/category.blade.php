@extends('layouts.frontend')

@section('title', $category->category_name)

@section('content')

{{-- ========================= --}}
{{-- CATEGORY HERO IMAGE --}}
{{-- ========================= --}}
@php
    $bannerPath = $category->banner_image ? public_path($category->banner_image) : null;
    $hasBannerImage = $category->banner_image && file_exists($bannerPath);
@endphp

@if($hasBannerImage)
<div class="category-hero-wrap">
    <img src="{{ asset($category->banner_image) }}"
         alt="{{ $category->category_name }}"
         class="category-hero-image">
</div>
@endif


{{-- ========================= --}}
{{-- CATEGORY TITLE + DESC --}}
{{-- ========================= --}}
<div class="category-page">
<div class="container category-title-wrap mb-5">

    @if($category->category_subtitle)
        <h1>
            {{ $category->category_subtitle }}
        </h1>
    @else
        <h1>
            {{ $category->category_name }}
        </h1>
    @endif

    @if($category->subtitle_description)
        <p>
            {{ $category->subtitle_description }}
        </p>
    @endif

</div>

</div>



{{-- ========================= --}}
{{-- ARTICLES GRID --}}
{{-- ========================= --}}
<div class="container category-content-wrap">

<div class="row">

@foreach($articles as $article)

<div class="col-lg-3 col-md-4 col-sm-6 mb-4">

    <div class="card h-100 shadow-sm border-0">

        <img src="{{ asset($article->article_featured_image_url ?: $article->featured_image_url) }}"
             class="card-img-top"
             style="height:240px; object-fit:cover;">

        <div class="card-body d-flex flex-column">

            <h6 class="fw-bold">
                {{ $article->title }}
            </h6>

            <p class="text-muted small flex-grow-1">
                {{ \Illuminate\Support\Str::limit(strip_tags($article->summary), 90) }}
            </p>

            <a href="{{ route('frontend.article', $article) }}"
               class="btn btn-outline-dark btn-sm mt-auto">
                Read More
            </a>

        </div>

    </div>

</div>

@endforeach

</div>


{{-- ========================= --}}
{{-- PAGINATION --}}
{{-- ========================= --}}
<div class="d-flex justify-content-center mt-4">
    {{ $articles->links('pagination::bootstrap-5') }}
</div>

</div>



{{-- ========================= --}}
{{-- CATEGORY SLIDER (BOTTOM) --}}
{{-- ========================= --}}
@if($category->bannerSliders->count())

<div class="container-fluid mt-5">

<div id="categorySlider"
     class="carousel slide"
     data-bs-ride="carousel" style="margin-top:10px">

<div class="carousel-inner">

@foreach($category->bannerSliders as $key=>$slider)

<div class="carousel-item {{ $key==0?'active':'' }}">

@if($slider->link)
<a href="{{ $slider->link }}">
@endif

<img src="{{ asset($slider->image) }}"
     class="d-block w-100"
     style=" object-fit:cover;">

@if($slider->link)
</a>
@endif

</div>

@endforeach

</div>

<button class="carousel-control-prev"
        type="button"
        data-bs-target="#categorySlider"
        data-bs-slide="prev">
<span class="carousel-control-prev-icon"></span>
</button>

<button class="carousel-control-next"
        type="button"
        data-bs-target="#categorySlider"
        data-bs-slide="next">
<span class="carousel-control-next-icon"></span>
</button>

</div>

</div>

@endif

@push('styles')
<style>
    .category-hero-wrap {
        width: calc(100% - 102px);
        margin: 0 auto 96px;
    }

    .category-hero-image {
        display: block;
        width: 100%;
        height: auto;
    }

    .category-page {
        padding: 0 0 20px;
        background: #fff;
    }

    .category-title-wrap,
    .category-content-wrap {
        max-width: none;
        padding-left: 51px;
        padding-right: 51px;
    }

    .category-title-wrap {
        text-align: left;
    }

    .category-title-wrap h1 {
        margin: 0 0 24px;
        color: #000;
        font-size: clamp(32px, 2.25vw, 42px);
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.18;
    }

    .category-title-wrap p {
        max-width: none;
        margin: 0;
        color: #000;
        font-size: clamp(18px, 1.15vw, 23px);
        line-height: 1.5;
    }

    .category-content-wrap .card {
        border-radius: 8px;
        overflow: hidden;
    }

    @media (max-width: 575.98px) {
        .category-page {
            padding-top: 22px;
        }

        .category-hero-wrap {
            width: calc(100% - 24px);
            margin-bottom: 44px;
        }

        .category-title-wrap,
        .category-content-wrap {
            padding-left: 20px;
            padding-right: 20px;
        }
    }
</style>
@endpush

@endsection
