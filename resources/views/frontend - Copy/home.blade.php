@extends('layouts.frontend')

@section('title', 'Home')

@section('content')

<!-- ================= HERO SLIDER ================= -->
<section class="w-full">

    <swiper-container
        speed="1500"
        autoplay="true"
        loop="true"
    >

        <swiper-slide>
            <img src="{{ asset('uploads/slider/cover3.jpeg') }}"
                 class="w-full h-full object-cover">
        </swiper-slide>

        <swiper-slide>
            <img src="{{ asset('uploads/slider/cover4a.jpeg') }}"
                 class="w-full h-full object-cover">
        </swiper-slide>

        <swiper-slide>
            <img src="{{ asset('uploads/slider/cover5.jpeg') }}"
                 class="w-full h-full object-cover">
        </swiper-slide>

         <swiper-slide>
            <img src="{{ asset('uploads/slider/cover52.jpg') }}"
                 class="w-full h-full object-cover">
        </swiper-slide>

    </swiper-container>

</section>


<!-- ================= LATEST ARTICLES ================= -->
<section class="max-w-[1200px] mx-auto mt-16 px-6">

    <h2 class="text-2xl font-bold mb-6">Latest Articles</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        @foreach($latestArticles ?? [] as $article)
            <div class="border rounded overflow-hidden shadow-sm hover:shadow-md transition duration-300">

                <img src="{{ asset($article->featured_image_url) }}"
                     class="w-full h-60 object-cover">

                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2">
                        {{ $article->title }}
                    </h3>

                    <p class="text-gray-600 text-sm mb-3">
                        {{ \Illuminate\Support\Str::limit($article->summary, 100) }}
                    </p>

                    <a href="{{ route('frontend.article', $article->id) }}"
                       class="text-blue-800 font-medium hover:underline">
                        Read More →
                    </a>
                </div>

            </div>
        @endforeach

    </div>

</section>

@endsection
