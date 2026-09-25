@extends('layouts.frontend')

@section('title', $article->title)

@push('styles')
<style>
    .reader-shell {
        user-select: none;
    }

    .reader-shell img {
        pointer-events: none;
    }

    .reader-page {
        background: #fbfaf8;
    }

    .reader-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 28px;
    }

    .reader-media {
        position: sticky;
        top: 104px;
    }

    .reader-media img {
        width: 100%;
        aspect-ratio: 4 / 5;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 18px 45px rgba(39, 27, 23, .14);
    }

    .reader-content {
        padding-bottom: 40px;
    }

    .reader-content h1 {
        color: #201918;
        font-size: clamp(34px, 5vw, 58px);
        line-height: 1.04;
        font-weight: 800;
        letter-spacing: 0;
        margin-bottom: 18px;
    }

    .reader-summary {
        color: #6e625d;
        font-size: 20px;
        line-height: 1.65;
        margin-bottom: 34px;
    }

    .reader-block {
        color: #2d2623;
        font-size: 17px;
        line-height: 1.9;
        margin-bottom: 28px;
    }

    .reader-block img,
    .reader-content > img {
        border-radius: 8px;
    }

    @media print {
        body * {
            visibility: hidden !important;
        }
    }

    @media (max-width: 991.98px) {
        .reader-media {
            position: static;
            margin-bottom: 28px;
        }

        .reader-media img {
            aspect-ratio: 16 / 10;
        }
    }
</style>
@endpush

@section('content')
<div class="reader-page py-5">
<div class="container reader-shell" oncontextmenu="return false">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="reader-toolbar">
        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-dark">Back</a>
        <span class="badge {{ ($article->access_type ?? 'free') === 'paid' ? 'text-bg-warning' : 'text-bg-secondary' }}">
            {{ ucfirst($article->access_type ?? 'free') }}
        </span>
    </div>

    <article class="row g-5">
        <div class="col-lg-5">
            <div class="reader-media">
                @if($article->featured_image_url)
                    <img src="{{ asset($article->featured_image_url) }}" alt="{{ $article->title }}">
                @else
                    <img src="{{ asset('images/mag.jpg') }}" alt="{{ $article->title }}">
                @endif

                @if($article->left_image_title)
                    <p class="text-muted mt-3 small">{{ $article->left_image_title }}</p>
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            <div class="reader-content">
                <h1>{{ $article->title }}</h1>

                @if($article->summary)
                    <p class="reader-summary">{{ $article->summary }}</p>
                @endif

                @foreach($article->blocks as $block)
                    @php $data = $block->block_data; @endphp

                    @if(!empty($data['content']))
                        <div class="reader-block">
                            {!! $data['content'] !!}
                        </div>
                    @endif

                    @if(!empty($data['image']))
                        <img src="{{ asset($data['image']) }}" class="img-fluid w-100 my-4" alt="">
                    @endif
                @endforeach
            </div>
        </div>
    </article>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('keydown', function (event) {
        const key = event.key.toLowerCase();
        if ((event.ctrlKey || event.metaKey) && ['s', 'p'].includes(key)) {
            event.preventDefault();
        }
    });
</script>
@endpush
