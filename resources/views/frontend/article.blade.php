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


            {{-- BUY BUTTON --}}
            @if($article->buy_button_link)
                <div class="mt-5">
                    <a href="{{ $article->buy_button_link }}"
                       target="_blank"
                       class="btn btn-dark px-4 py-2">
                        Buy Now
                    </a>
                </div>
            @endif

        </div>

    </div>

</div>

@endsection
