@extends('layouts.frontend')

@section('title', 'Home')

@section('content')

<div class="home-page">
<!-- ================= HERO SLIDER ================= -->
<section class="container home-hero-section">
<div class="swiper hero-slider">
    <!-- <div class="swiper-wrapper">

        <div class="swiper-slide">
            <img src="{{ asset('uploads/slider/cover3.jpeg') }}">
        </div>

        <div class="swiper-slide">
            <img src="{{ asset('uploads/slider/cover5.jpeg') }}">
        </div>

        <div class="swiper-slide">
            <img src="{{ asset('uploads/slider/cover4a.jpeg') }}">
        </div>


        <div class="swiper-slide">
            <img src="{{ asset('uploads/slider/cover52.jpg') }}">
        </div>


    </div> -->

     <div class="swiper-wrapper">

        @foreach($banners as $banner)

        <div class="swiper-slide">
            <img src="{{ asset($banner->image) }}" class="w-100">
        </div>

        @endforeach

    </div>
</div>
</section>

<!-- ================= FEATURED SECTION ================= -->

<section class="py-5 bg-light">

    <div class="container">

        <h2 class="text-center fw-bold mb-5">
            LATEST MAGAZINE FEATURES
        </h2>

        <div class="row align-items-center">

            {{-- LEFT IMAGE --}}
            <div class="col-lg-5 mb-4 mb-lg-0">
                @if($latestArticles && $latestArticles->first())
                    <img src="{{ asset($latestArticles->first()->article_featured_image_url ?: $latestArticles->first()->featured_image_url) }}"
                         class="img-fluid w-100 shadow">
                @else
                    <img src="{{ asset('uploads/banners/1770176316.jpg') }}"
                         class="img-fluid w-100 shadow">
                @endif
            </div>

            {{-- RIGHT CONTENT BOX --}}
            <div class="col-lg-7">

                <div class="p-5 text-white"
                     style="background-color: #243f63;">

                    @if($latestArticles && $latestArticles->first())
                        <h3 class="fw-bold text-warning">
                            {{ $latestArticles->first()->title }}
                        </h3>

                        <p class="mb-4">
                            {{ $latestArticles->first()->summary }}
                        </p>

                        <a href="{{ route('frontend.article', $latestArticles->first()) }}"
                           class="btn btn-light px-4">
                            Read More
                        </a>
                    @else
                        <h3 class="fw-bold text-warning">
                            Latest Article
                        </h3>

                        <p class="mb-4">
                            Check out our latest featured content
                        </p>
                    @endif

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= LATEST ARTICLES ================= -->
<section class="past-magazines-section">
<div class="container">

    <div class="past-magazines-heading text-center">
        <h3>{{ $articleSearch ? 'SEARCH RESULTS' : 'PAST MAGAZINES' }}</h3>
    </div>

    @if(($pastIssues ?? collect())->isNotEmpty())
        <div class="past-magazines-carousel-wrap">
            <button class="past-magazines-nav past-magazines-prev" type="button" aria-label="Previous past magazines">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="swiper past-magazines-slider" aria-label="Past magazines">
                <div class="swiper-wrapper">
                    @foreach($pastIssues as $article)
                        <div class="swiper-slide">
                            <article class="past-magazine-card">
                                <img src="{{ asset($article->article_featured_image_url ?: $article->featured_image_url ?: 'images/mag.jpg') }}"
                                     alt="{{ $article->title }}">

                                <div class="past-magazine-card-body">
                                    <h5>{{ $article->title }}</h5>

                                    <a href="{{ route('frontend.article', $article) }}">
                                        Read More
                                    </a>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>

            <button class="past-magazines-nav past-magazines-next" type="button" aria-label="Next past magazines">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    @else
        <div class="home-empty-state">
            No past magazines found{{ $articleSearch ? ' for "' . $articleSearch . '"' : '' }}.
        </div>
    @endif

</div>
</section>

<!-- ================= UPCOMING EVENTS ================= -->
<!-- ================= EVENTS ================= -->
<section class="events-section">
<div class="container">

    <div class="events-heading text-center">
        <h3>Events</h3>
    </div>

    <div class="events-carousel-wrap">
        <button class="events-nav events-prev" type="button" aria-label="Previous events">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="swiper events-slider" aria-label="Upcoming events">
            <div class="swiper-wrapper">

        @foreach($events ?? [] as $event)

            <div class="swiper-slide">
            <article class="event-card">

                @if($event->image)
                    <img src="{{ asset($event->image) }}" alt="{{ $event->title }}">
                @else
                    <img src="{{ asset('images/mag.jpg') }}" alt="{{ $event->title }}">
                @endif

                <div class="event-card-body">
                    @if($event->subtitle)
                        <p class="event-date">{{ $event->subtitle }}</p>
                    @endif

                    <h5>{{ $event->title }}</h5>

                    <p>
                        {{ \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($event->description)), 120) }}
                    </p>

                    <a href="{{ route('frontend.event', $event->id) }}">
                        View Event
                    </a>
                </div>

            </article>
            </div>

        @endforeach

            </div>
        </div>

        <button class="events-nav events-next" type="button" aria-label="Next events">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

</div>
</section>


</div>

@endsection

@push('scripts')
<script>
    new Swiper('.hero-slider', {
        loop: true,
        autoplay: {
            delay: 3000,
        },
    });

    new Swiper('.past-magazines-slider', {
        loop: {{ $pastIssues->count() > 4 ? 'true' : 'false' }},
        spaceBetween: 24,
        slidesPerView: 1,
        navigation: {
            nextEl: '.past-magazines-next',
            prevEl: '.past-magazines-prev',
        },
        breakpoints: {
            768: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 4,
            },
        },
    });

    new Swiper('.events-slider', {
        loop: {{ $events->count() > 4 ? 'true' : 'false' }},
        spaceBetween: 26,
        slidesPerView: 1,
        navigation: {
            nextEl: '.events-next',
            prevEl: '.events-prev',
        },
        breakpoints: {
            768: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 4,
            },
        },
    });
</script>
@endpush

@push('styles')
<style>
    .home-page {
        background: #fff;
        padding-top: 34px;
    }

    .home-empty-state {
        padding: 30px;
        border: 1px solid #e7e0dc;
        border-radius: 8px;
        color: #655c55;
        font-weight: 700;
        text-align: center;
    }

    .past-magazines-section {
        padding: 72px 0 42px;
        background: #fff;
    }

    .past-magazines-heading h3 {
        position: relative;
        display: inline-block;
        margin: 0 0 60px;
        color: #000;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .past-magazines-heading h3::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -12px;
        width: 76px;
        height: 4px;
        background: #e19600;
        transform: translateX(-50%);
    }

    .past-magazines-carousel-wrap {
        position: relative;
    }

    .past-magazines-slider {
        padding: 0 1px 4px;
    }

    .past-magazine-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
    }

    .past-magazine-card img {
        width: 100%;
        aspect-ratio: 3 / 4;
        object-fit: cover;
        display: block;
    }

    .past-magazine-card-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding-top: 22px;
    }

    .past-magazine-card h5 {
        min-height: 70px;
        margin: 0 0 18px;
        color: #000;
        font-size: 26px;
        font-weight: 400;
        line-height: 1.35;
    }

    .past-magazine-card a {
        display: block;
        width: 100%;
        padding: 14px 18px;
        margin-top: auto;
        border: 1px solid #111;
        color: #000;
        font-size: 22px;
        line-height: 1.25;
        text-align: center;
        text-decoration: none;
        transition: background .2s ease, color .2s ease;
    }

    .past-magazine-card a:hover {
        background: #111;
        color: #fff;
    }

    .past-magazines-nav {
        position: absolute;
        top: 40%;
        z-index: 3;
        width: 44px;
        height: 64px;
        border: 0;
        background: transparent;
        color: #0078ff;
        font-size: 40px;
        line-height: 1;
        transform: translateY(-50%);
        transition: color .2s ease, transform .2s ease;
    }

    .past-magazines-nav:hover,
    .past-magazines-nav:focus {
        color: #003e7d;
        outline: 0;
        transform: translateY(-50%) scale(1.04);
    }

    .past-magazines-prev {
        left: -48px;
    }

    .past-magazines-next {
        right: -48px;
    }

    .home-hero-section {
        margin-bottom: 48px;
    }

    .home-hero-section .hero-slider {
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 18px 45px rgba(43, 31, 27, .1);
    }

    .home-hero-section .swiper-slide img {
        display: block;
    }

    .events-section {
        padding: 72px 0 56px;
        background: #fff;
    }

    .events-heading h3 {
        position: relative;
        display: inline-block;
        margin: 0 0 60px;
        color: #000;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .events-heading h3::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -12px;
        width: 76px;
        height: 4px;
        background: #e19600;
        transform: translateX(-50%);
    }

    .events-carousel-wrap {
        position: relative;
    }

    .events-slider {
        padding: 0 2px 10px;
    }

    .events-nav {
        position: absolute;
        top: 42%;
        z-index: 3;
        width: 48px;
        height: 64px;
        border: 0;
        background: transparent;
        color: #0078ff;
        font-size: 40px;
        line-height: 1;
        transform: translateY(-50%);
        transition: color .2s ease, transform .2s ease;
    }

    .events-nav:hover,
    .events-nav:focus {
        color: #003e7d;
        outline: 0;
        transform: translateY(-50%) scale(1.04);
    }

    .events-prev {
        left: -46px;
    }

    .events-next {
        right: -46px;
    }

    .event-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 610px;
        border: 0;
        border-radius: 0;
        background: #fff;
        box-shadow: none;
    }

    .event-card img {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        display: block;
    }

    .event-card-body {
        display: flex;
        flex-direction: column;
        flex: 1;
        padding: 24px 0 0;
    }

    .event-date {
        order: 2;
        display: inline;
        align-self: flex-start;
        margin: 0 0 18px;
        color: #565656;
        font-size: 19px;
        font-weight: 600;
        line-height: 1.45;
        border-bottom: 2px solid #e19600;
    }

    .event-card h5 {
        order: 1;
        min-height: 106px;
        margin: 0 0 20px;
        color: #153a67;
        font-size: 26px;
        font-weight: 500;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .event-card p:not(.event-date) {
        order: 3;
        color: #222;
        font-size: 21px;
        line-height: 1.45;
    }

    .event-card a {
        order: 4;
        display: block;
        width: 100%;
        padding: 14px 18px;
        margin-top: auto;
        background: #24496f;
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
    }

    .event-card a:hover {
        background: #153a67;
        color: #fff;
        text-decoration: none;
    }

    @media (max-width: 575.98px) {
        .home-page {
            padding-top: 20px;
        }

        .home-hero-section {
            margin-bottom: 34px;
        }

        .past-magazines-section {
            padding: 48px 0 34px;
        }

        .past-magazines-heading h3 {
            margin-bottom: 38px;
            font-size: 30px;
        }

        .past-magazines-nav {
            display: none;
        }

        .past-magazine-card h5 {
            min-height: 0;
            font-size: 22px;
        }

        .past-magazine-card a {
            font-size: 20px;
        }

        .events-section {
            padding: 48px 0 42px;
        }

        .events-heading h3 {
            margin-bottom: 38px;
            font-size: 30px;
        }

        .events-nav {
            display: none;
        }

        .event-card {
            min-height: 0;
        }

        .event-card h5 {
            min-height: 0;
            font-size: 22px;
        }

        .event-card p:not(.event-date) {
            font-size: 18px;
        }
    }
</style>
@endpush
