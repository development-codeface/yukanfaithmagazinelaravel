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

<section class="py-5  latest-magazines">

    <div class="container">

        <h2 class="text-center fw-bold mb-5">
            LATEST MAGAZINE FEATURES
        </h2>

        <div class="row align-items-center latest-feature">

            {{-- LEFT IMAGE --}}
            <div class="col-lg-5 mb-4 mb-lg-0 feature-image">
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
                        <h3 class="feature-title">
                            {{ $latestArticles->first()->title }}
                        </h3>

                        <p class="mb-4">
                            {{ $latestArticles->first()->summary }}
                        </p>

                        @include('partials.home-magazine-actions', ['article' => $latestArticles->first()])
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


<!-- ================= PAST MAGAZINE ARTICLE PREVIEWS ================= -->


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

                                    @include('partials.home-magazine-actions', ['article' => $article])
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="past-magazines-pagination" aria-label="Choose magazine slide"></div>

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

@include('partials.ad-banners')

<!-- ================= UPCOMING EVENTS ================= -->
<!-- ================= EVENTS ================= -->
<section class="events-section">
<div class="container">

    <div class="events-heading text-center">
        <h3>Events</h3>
    </div>

    <div class="events-carousel-wrap">
        {{-- <button class="events-nav events-prev" type="button" aria-label="Previous events">
            <i class="fa-solid fa-chevron-left"></i>
        </button> --}}

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

        <div class="events-pagination" aria-label="Choose event slide"></div>

        {{-- <button class="events-nav events-next" type="button" aria-label="Next events">
            <i class="fa-solid fa-chevron-right"></i>
        </button> --}}
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
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        pagination: {
            el: '.past-magazines-pagination',
            clickable: true,
            bulletClass: 'past-magazines-pagination-bullet',
            bulletActiveClass: 'is-active',
            renderBullet: (index, className) => `<button type="button" class="${className}" aria-label="Go to magazine slide ${index + 1}"></button>`,
        },
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
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        pagination: {
            el: '.events-pagination',
            clickable: true,
            bulletClass: 'events-pagination-bullet',
            bulletActiveClass: 'is-active',
            renderBullet: (index, className) => `<button type="button" class="${className}" aria-label="Go to event slide ${index + 1}"></button>`,
        },
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
 .feature-title {
    /* margin-top: -40%; */
    font-size: 36px;
    font-weight: bold;
    color: #da9103;
    display: flex;
    align-items: center;
    margin-bottom: 7px;
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
        padding: 72px 0 120px;
        background: #fff;
    }

    .past-magazines-heading h3 {
            position: relative;
    display: inline-block;
    margin: 0 0 60px;
    color: #000000;
    font-size: 36px;
    font-weight: 700;
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

    .past-magazines-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
        margin-top: 18px;
    }

    .past-magazines-pagination-bullet {
        width: 12px;
        height: 12px;
        padding: 0;
        border: 2px solid #243f63;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
    }

    .past-magazines-pagination-bullet.is-active {
        background: #da9103;
        border-color: #da9103;
    }

    .past-magazines-slider .swiper-wrapper,
    .events-slider .swiper-wrapper {
        align-items: stretch;
    }

    .past-magazines-slider .swiper-slide,
    .events-slider .swiper-slide {
        height: auto;
    }

    .past-magazine-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        border: 1px solid #e6ded4;
        /* border-radius: 0px; */
        background: #f8f5ef;
        /* box-shadow: 0 8px 24px rgba(31, 48, 66, .10); */
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .past-magazine-card:hover {
        transform: translateY(-4px);
        /* box-shadow: 0 14px 30px rgba(31, 48, 66, .16); */
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
        /* min-height: 200px; */
        flex-direction: column;
        padding: 22px;
        border-top: 4px solid #d5a64b;
        background: #f4f4f4;
    }

    .past-magazine-card h5 {
        /* min-height: 70px; */
        margin: 0 0 15px;
        color: #243f63;
        font-size: 21px;
        font-weight: 400;
        line-height: 1.35;
    }

    .magazine-actions {
        text-transform: uppercase;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: auto;
        padding-top: 22px;
    }
    .past-magazine-card .magazine-actions { margin-top: auto; padding-top: 10px; }
    .magazine-action {
text-transform: uppercase;
        display: inline-flex;
        flex: 1 1 130px;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 46px;
        padding: 12px 18px;
        border: 1px solid #243f63;
        border-radius: 0px;
        background: #fff;
        color: #243f63;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        text-align: center;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .magazine-action--subscribe { background: #24496f; color: #fff; }
    .magazine-action--buy { flex-basis: 100%; background: #f8f6f4; color: #55443b; font-size: 14px; }
    .magazine-action--read:only-child { border-color: #243f63; background: #243f63; color: #fff; }
    .magazine-action:hover { border-color: #243f63; background: #edf2f8; color: #243f63; }
    .magazine-action--subscribe:hover { border-color: #153a67; background: #153a67; color: #fff; }
    .magazine-action--read:only-child:hover { background: #192f4d; color: #fff; }
    .magazine-action:focus-visible { outline: 3px solid #e6ac4c; outline-offset: 3px; }
    .magazine-action[aria-disabled="true"] { opacity: .55; cursor: not-allowed; }

    .past-magazines-nav {
  position: absolute;
    bottom: -13%;
    z-index: 3;
    width: 40px;
display:none;
    height: 40px;
    border: 1px solid #e89145;
    background: #da9103;
    color: #ffffff;
    font-size: 20px;
    border-radius: 100%;
    line-height: 1;
/* display: flex;
align-items: center; */
justify-content: center;
    transform: translateY(-50%);
    transition: color .2s ease, transform .2s ease;
    }

    .past-magazines-nav:hover,
    .past-magazines-nav:focus {
        color: #ffffff;
 background: #003e7d;
        outline: 0;
        transform: translateY(-50%) scale(1.04);
    }

    .past-magazines-prev {
        left: 46%;
    }

    .past-magazines-next {
        left: 50%;
    }

    .home-hero-section {
        margin-bottom: 48px;
    }

    .home-hero-section .hero-slider {
        overflow: hidden;
        border-radius: 0px;
        /* box-shadow: 0 18px 45px rgba(43, 31, 27, .1); */
    }

    .home-hero-section .swiper-slide img {
        display: block;
    }

    .events-section {
        padding: 120px 0 56px;
        background: #fff;
    }

    .events-heading h3 {
        position: relative;
        display: inline-block;
        margin: 0 0 60px;
        color: #000;
        font-size: 36px;
        font-weight: 700;
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

    .events-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
        margin-top: 18px;
    }

    .events-pagination-bullet {
        width: 12px;
        height: 12px;
        padding: 0;
        border: 2px solid #243f63;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
    }

    .events-pagination-bullet.is-active {
        background: #da9103;
        border-color: #da9103;
    }

    .events-nav {
    position: absolute;
    bottom: -13%;
    z-index: 3;
    width: 40px;
    height: 40px;
    border: 1px solid #e89145;
    background: #da9103;
    color: #ffffff;
    font-size: 20px;
    border-radius: 100%;
    line-height: 1;
display: flex;
align-items: center;
justify-content: center;
    transform: translateY(-50%);
    transition: color .2s ease, transform .2s ease;
    }

    .events-nav:hover,
    .events-nav:focus {
        color: #ffffff;
        background: #003e7d;
        outline: 0;
        transform: translateY(-50%) scale(1.04);
    }

    .events-prev {
        left: 46%;
    }

    .events-next {
        left: 50%;
    }

    .event-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 500px;
        border: 0;
        border-radius: 0;
        background: #fff;
transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: none;
    }


    .event-card:hover {
        transform: translateY(-4px);
        /* box-shadow: 0 14px 30px rgba(31, 48, 66, .16); */
    }

    .event-card img {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        display: block;
    }
.col-lg-5.mb-4.mb-lg-0.feature-image img {
    max-width: 450px;
    object-fit: cover;
    max-height: 550px;
}
.col-lg-5.mb-4.mb-lg-0.feature-image {
    max-height: 550px;
    max-width: fit-content;
}
.row.align-items-center.latest-feature {
    align-items: end !important;
}
a.magazine-action.magazine-action--read:hover {
    color: white;
    background-color: #da9103;
border-color: #da9103;
}
.magazine-action--subscribe:hover {
    border-color: #da9103;
    color: white;
    background-color: #da9103;
}
    .event-card-body {
    /* min-height: 410px; */
    flex: 1;
    flex-direction: column;
    padding: 22px;
    display: flex;
    flex-direction: column;
    padding: 22px;
    border-top: 4px solid #d5a64b;
    background: #f4f4f4;

    }

    .event-date {
        order: 2;
        display: inline;
        align-self: flex-start;
        margin: 0 0 18px;
        color: #131313;
        font-size: 19px;
        font-weight: 500;
        line-height: 1.45;
        padding-bottom: 5px;
        border-bottom: 2px solid #e19600;
    }

    .event-card h5 {
        order: 1;
        /* min-height: 106px; */
        margin: 0 0 20px;
        color: #153a67;
        font-size: 20px;
        font-weight: 500;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .event-card p:not(.event-date) {
        order: 3;
        color: #000000;
        font-size: 18px;
        line-height: 1.45;
    }

    .event-card a {
text-transform: uppercase;
        order: 4;
        display: block;
        width: 100%;
        padding: 14px 18px;
        margin-top: auto;
        background: #24496f;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
    line-height: 1.35;
        text-align: center;
        text-decoration: none;
    }

    .event-card a:hover {
        background: #153a67;
        color: #fff;
        text-decoration: none;
    }

.latest-magazines h2::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    right: 50%;
    width: 60px;
    height: 3px;
    background-color: #da9103;
    transform: translate(-50%, -50%);
}
.latest-magazines h2{
position: relative;
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

        .magazine-action {
            font-size: 14px;
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

.event-card a:hover{
    color: white;
    background-color: #da9103;
    border-color: #da9103;
}

.events-section .container{
    max-width: 1440px;
}
</style>
@endpush
