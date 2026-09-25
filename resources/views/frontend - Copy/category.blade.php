@extends('layouts.frontend')

@section('title', $category->category_name)

@section('content')
<h2>{{ $category->category_name }}</h2>

@foreach($articles as $article)
    <div class="card mb-3">
        <img src="{{ asset($article->featured_image_url) }}" class="card-img-top">
        <div class="card-body">
            <h5>{{ $article->title }}</h5>
            <p>{{ Str::limit($article->summary, 130) }}</p>
            <a href="{{ route('frontend.article', $article->id) }}" class="btn btn-sm btn-primary">
                Read More
            </a>
        </div>
    </div>
@endforeach

{{ $articles->links() }}
@endsection
