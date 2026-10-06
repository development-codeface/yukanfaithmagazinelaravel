@extends('layouts.frontend')

@section('title', 'My Dashboard')

@section('content')
<div class="user-dashboard-page py-5">
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="dashboard-hero mb-4">
        <div>
            <p class="dashboard-eyebrow">Member area</p>
            <h1>My Dashboard</h1>
            <p>{{ auth()->user()->name }} - {{ auth()->user()->email }}</p>
        </div>

        <div class="dashboard-quick-links">
            <a href="{{ route('user.subscriptions') }}" class="btn btn-dark">
                Manage Subscription
            </a>
            <a href="#articles" class="btn btn-outline-dark">
                Articles
            </a>
            <!-- <a href="#magazines" class="btn btn-outline-dark">
                Reader
            </a> -->
        </div>
    </div>

    <div class="dashboard-stats mb-4">
        <div class="dashboard-stat">
            <span>Available Articles</span>
            <strong>{{ $articles->total() }}</strong>
        </div>
        <!-- <div class="dashboard-stat">
            <span>Magazine Issues</span>
            <strong>{{ $magazines->count() }}</strong>
        </div> -->
        <div class="dashboard-stat">
            <span>Subscription</span>
            <strong>{{ $subscription ? 'Active' : 'None' }}</strong>
        </div>
    </div>

    <section class="mb-5 dashboard-panel">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <p class="section-kicker mb-1">Plan access</p>
                <h3 class="fw-bold mb-0">Subscription</h3>
            </div>
            <a href="{{ route('user.subscriptions') }}" class="btn btn-dark">Manage</a>
        </div>

        @if($subscription)
            <div class="subscription-summary">
                <h5 class="mb-2">{{ $subscription->plan->name ?? 'Active Plan' }}</h5>
                <p class="mb-1">Status: <strong>{{ ucfirst($subscription->status) }}</strong></p>
                <p class="mb-0">
                    Valid until:
                    {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') : 'No expiry date' }}
                </p>
            </div>
        @else
            <div class="subscription-summary">
                <h5 class="mb-2">No active subscription</h5>
                <p class="text-muted mb-0">Choose a plan to unlock paid articles and magazine reader access.</p>
            </div>
        @endif
    </section>

    <section class="mb-5 dashboard-content-section" id="articles">
        <div class="section-heading article-toolbar">
            <div>
                <p class="section-kicker">Library</p>
                <h3>Articles</h3>
            </div>

            <form method="GET" action="{{ route('user.dashboard') }}#articles" class="article-search-form">
                <label for="dashboard-article-search" class="visually-hidden">Search articles</label>
                <div class="article-search-input">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="dashboard-article-search"
                        name="search"
                        value="{{ $articleSearch }}"
                        class="form-control"
                        placeholder="Search articles">
                </div>
                <button class="btn btn-dark article-search-action" type="submit" aria-label="Search articles">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
                @if($articleSearch)
                    <a href="{{ route('user.dashboard') }}#articles" class="btn btn-outline-dark article-search-action" aria-label="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>
        </div>
        <div class="row g-4">
            @forelse($articles as $article)
                @php
                    $canReadArticle = auth()->user()->canReadArticle($article);
                @endphp
                <div class="col-md-4">
                    <div class="card dashboard-article-card h-100">
                        @if($article->article_featured_image_url || $article->featured_image_url)
                            <img src="{{ asset($article->article_featured_image_url ?: $article->featured_image_url) }}" class="card-img-top" alt="">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <h5 class="card-title mb-0">{{ $article->title }}</h5>
                                <span class="badge {{ ($article->access_type ?? 'free') === 'paid' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                                    {{ ucfirst($article->access_type ?? 'free') }}
                                </span>
                            </div>
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit($article->summary, 110) }}</p>
                            @if($canReadArticle)
                                <a href="{{ route('user.article', $article) }}" class="btn btn-outline-dark mt-auto">Read Article</a>
                            @else
                                <form method="POST" action="{{ route('user.article.purchase', $article) }}" class="mt-auto">
                                    @csrf
                                    <button class="btn btn-dark w-100">
                                        Buy Article
                                        @if($article->single_article_price)
                                            - ${{ number_format($article->single_article_price, 2) }}
                                        @endif
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-article-state">
                        No articles found{{ $articleSearch ? ' for "' . $articleSearch . '"' : '' }}.
                    </div>
                </div>
            @endforelse
        </div>

        @if($articles->hasPages())
            <div class="mt-4">
                {{ $articles->fragment('articles')->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    <!-- <section class="dashboard-content-section" id="magazines">
        <div class="section-heading">
            <p class="section-kicker">Reader</p>
            <h3>Magazine Reader</h3>
        </div>
        <div class="row g-4">
            @foreach($magazines as $magazine)
                <div class="col-md-4">
                    <div class="card dashboard-magazine-card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $magazine->title }}</h5>
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit($magazine->description, 120) }}</p>
                            <a href="{{ route('user.magazine.reader', $magazine) }}" class="btn btn-outline-dark mt-auto">Open Reader</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section> -->
</div>
</div>
@endsection

@push('styles')
<style>
    .user-dashboard-page {
        background: #fbfaf8;
    }

    .dashboard-panel,
    .subscription-summary {
        border: 1px solid #e7e0dc;
        background: #fff;
        border-radius: 8px;
    }

    .dashboard-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        padding: 34px;
        border-radius: 8px;
        background: #8e2d26;
        color: #fff;
        box-shadow: 0 20px 55px rgba(61, 39, 34, .14);
    }

    .dashboard-eyebrow,
    .section-kicker {
        margin: 0;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .dashboard-eyebrow {
        color: rgba(255, 255, 255, .78);
    }

    .dashboard-hero h1 {
        color: #fff;
        font-size: clamp(34px, 4vw, 52px);
        font-weight: 800;
        letter-spacing: 0;
        margin: 8px 0 8px;
    }

    .dashboard-hero p:not(.dashboard-eyebrow) {
        color: rgba(255, 255, 255, .82);
        margin: 0;
    }

    .dashboard-quick-links {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .dashboard-quick-links .btn {
        border-radius: 8px;
        font-weight: 800;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .dashboard-stat {
        padding: 22px;
        border: 1px solid #e7e0dc;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(43, 31, 27, .06);
    }

    .dashboard-stat span {
        display: block;
        color: #716761;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .dashboard-stat strong {
        color: #201918;
        font-size: 30px;
        font-weight: 800;
    }

    .dashboard-panel {
        padding: 24px;
    }

    .subscription-summary {
        padding: 20px;
        background: #fbfaf8;
    }

    .section-heading {
        margin-bottom: 18px;
    }

    .article-toolbar {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
    }

    .article-search-form {
        display: flex;
        align-items: center;
        gap: 8px;
        width: min(100%, 540px);
        padding: 8px;
        border: 1px solid #e7e0dc;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 16px 38px rgba(35, 28, 24, .08);
    }

    .article-search-input {
        position: relative;
        flex: 1 1 auto;
        min-width: 0;
    }

    .article-search-input i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #8e2d26;
        font-size: 15px;
        pointer-events: none;
    }

    .article-search-form .form-control {
        min-height: 44px;
        border: 0;
        border-radius: 999px;
        padding-left: 44px;
        box-shadow: none;
    }

    .article-search-form .form-control:focus {
        box-shadow: none;
    }

    .article-search-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 999px;
        padding: 0;
    }

    .empty-article-state {
        padding: 28px;
        border: 1px solid #e7e0dc;
        border-radius: 8px;
        background: #fff;
        color: #716761;
        font-weight: 700;
        text-align: center;
    }

    .section-kicker {
        color: #8e2d26;
        margin-bottom: 5px;
    }

    .section-heading h3 {
        color: #201918;
        font-size: 28px;
        font-weight: 800;
        margin: 0;
    }

    .card {
        border-radius: 8px;
        border-color: #e7e0dc;
        box-shadow: 0 12px 34px rgba(43, 31, 27, .06);
        overflow: hidden;
    }

    .card-img-top {
        aspect-ratio: 16 / 10;
        object-fit: cover;
    }

    .dashboard-article-card .card-title,
    .dashboard-magazine-card .card-title {
        color: #201918;
        font-weight: 800;
    }

    @media (max-width: 767.98px) {
        .dashboard-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 22px;
        }

        .dashboard-quick-links {
            width: 100%;
            justify-content: flex-start;
        }

        .dashboard-quick-links .btn {
            flex: 1 1 150px;
        }

        .dashboard-stats {
            grid-template-columns: 1fr;
        }

        .article-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .article-search-form {
            width: 100%;
            border-radius: 8px;
        }
    }
</style>
@endpush
