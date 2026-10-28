@extends('layouts.frontend')

@section('title', $article->title)

@section('content')

<div class="container my-5">

    <div class="row g-5">

        {{-- LEFT IMAGE COLUMN --}}
        <div class="col-lg-5">

            <div class="position-sticky" style="top: 100px;">

                <img src="{{ asset($article->featured_image_url) }}"
                     class="img-fluid w-100">

                @if($article->left_image_title)
                    <p class="text-muted mt-2 small">
                        {{ $article->left_image_title }}
                    </p>
                @endif

            </div>

        </div>


{{-- RIGHT CONTENT COLUMN --}}
        <div class="col-lg-7">
            @php
                $displayHeading = trim($article->summary ?: $article->title);
                $duplicateHeadings = array_filter([
                    trim($article->title),
                    trim($article->summary ?? ''),
                ]);
            @endphp

            @if($displayHeading)
                <h1 class="fw-bold mb-4">
                    {{ $displayHeading }}
                </h1>
            @endif 


            {{-- ARTICLE BLOCKS --}}
            @if($canReadArticle)
            @foreach($article->blocks as $block)

                @php
                    $data = $block->block_data;
                    $content = $data['content'] ?? '';

                    if (!empty($content)) {
                        $content = preg_replace_callback(
                            '/<h[1-2][^>]*>(.*?)<\/h[1-2]>/is',
                            function ($matches) use ($duplicateHeadings) {
                                $headingText = trim(html_entity_decode(strip_tags($matches[1])));

                                foreach ($duplicateHeadings as $duplicateHeading) {
                                    if ($headingText === trim(html_entity_decode($duplicateHeading))) {
                                        return '';
                                    }
                                }

                                return $matches[0];
                            },
                            $content
                        );

                        $content = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $content);
                    }
                @endphp

                 @if(!empty(trim(strip_tags($content))))
                    <div class="mb-4" style="line-height: 1.8;">
                        {!! $content !!}
                    </div>
                @endif

                @if(!empty($data['image']))
                    <div class="my-4">
                        <img src="{{ asset($data['image']) }}"
                             class="img-fluid w-100">
                    </div>
                @endif

               

            @endforeach
            @if($article->blocks->isEmpty() && $article->content)
                <div class="mb-4" style="line-height: 1.8;">{!! $article->content !!}</div>
            @endif
            @else
                <p>{{ $article->issue ? 'This article is part of '.$article->issue->title.'. Buy the magazine or use a subscription covering its issue date to read it.' : 'Sign in with access to read the full article.' }}</p>
            @endif


            {{-- BUY BUTTON --}}
            @if($article->issue)
                <div class="mt-5">
                    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                    @if(auth()->check() && auth()->user()->canReadMagazine($article->issue))
                        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-dark mb-3">Read from My Dashboard</a>
                    @endif
                    <a href="{{ route('user.article.magazine.buy', $article) }}" class="btn btn-dark px-4 py-3">
                        Buy this Magazine
                    </a>
                </div>
            @else
                <div class="mt-5">
                    <button type="button" class="btn btn-dark px-4 py-2" disabled>
                        Buy this Magazine
                    </button>
                    <p class="text-muted mt-2">This issue is not available for purchase yet.</p>
                </div>
            @endif

        </div>

    </div>

</div>

@endsection
