@extends('layouts.admin')

@section('content')
<div class="magazine-dashboard">
    <div class="dashboard-hero mb-4">
        <div>
            <p class="dashboard-eyebrow">Magazine overview</p>
            <h1>Admin Dashboard</h1>
            <p class="dashboard-copy">Live content, reader, and subscription summary from the current database.</p>
        </div>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-light">
            <i class="fa fa-plus mr-1"></i> New Article
        </a>
    </div>

    @php
        $cards = [
            ['label' => 'Total Articles', 'value' => $dashboardStats['articles'], 'class' => 'primary', 'icon' => 'fa-newspaper-o'],
            ['label' => 'Published', 'value' => $dashboardStats['published'], 'class' => 'success', 'icon' => 'fa-check-circle'],
            ['label' => 'Drafts', 'value' => $dashboardStats['drafts'], 'class' => 'warning', 'icon' => 'fa-pencil'],
            ['label' => 'Active Subscriptions', 'value' => $dashboardStats['activeSubscriptions'], 'class' => 'danger', 'icon' => 'fa-credit-card'],
            ['label' => 'Categories', 'value' => $dashboardStats['categories'], 'class' => 'info', 'icon' => 'fa-list'],
            ['label' => 'Magazine Issues', 'value' => $dashboardStats['issues'], 'class' => 'secondary', 'icon' => 'fa-book'],
            ['label' => 'Authors', 'value' => $dashboardStats['authors'], 'class' => 'primary', 'icon' => 'fa-users'],
            ['label' => 'Active Plans', 'value' => $dashboardStats['activePlans'], 'class' => 'success', 'icon' => 'fa-tags'],
        ];
    @endphp

    <div class="row">
        @foreach ($cards as $card)
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-stat-card dashboard-stat-{{ $card['class'] }}">
                    <div>
                        <p>{{ $card['label'] }}</p>
                        <h2>{{ number_format($card['value']) }}</h2>
                    </div>
                    <span><i class="fa {{ $card['icon'] }}"></i></span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-line-chart mr-2"></i>
                        Publishing Activity
                    </h4>
                </div>
                <div class="card-body chart-box">
                    <canvas id="articleChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-pie-chart mr-2"></i>
                        Article Status
                    </h4>
                </div>
                <div class="card-body status-list">
                    <div><span>Published</span><strong>{{ number_format($dashboardStats['published']) }}</strong></div>
                    <div><span>Drafts</span><strong>{{ number_format($dashboardStats['drafts']) }}</strong></div>
                    <div><span>Archived</span><strong>{{ number_format($dashboardStats['archived']) }}</strong></div>
                    <div><span>Total Readers</span><strong>{{ number_format($dashboardStats['readers']) }}</strong></div>
                </div>
            </div>
        </div>
    </div> -->

    <div class="card dashboard-card mb-4">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="fa fa-list mr-2"></i>
                Category Overview
            </h4>
        </div>

        <div class="card-body table-responsive">
            <table class="table dashboard-table text-center mb-0">
                <thead>
                    <tr>
                        <th class="text-left">Category</th>
                        <th>Articles</th>
                        <th>Published</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categoryOverview as $category)
                        <tr>
                            <td class="text-left">{{ $category->category_name }}</td>
                            <td>{{ number_format($category->articles_count) }}</td>
                            <td>{{ number_format($category->published_count) }}</td>
                            <td>
                                @if ($category->articles_count > 0)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">No Articles</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted py-4">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="fa fa-pencil-square-o mr-2"></i>
                Recent Articles
            </h4>
        </div>

        <div class="card-body table-responsive">
            <table class="table dashboard-table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Published On</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentArticles as $article)
                        <tr>
                            <td>{{ $article->title }}</td>
                            <td>{{ optional($article->author)->name ?? 'Not assigned' }}</td>
                            <td>{{ optional($article->category)->category_name ?? 'Uncategorized' }}</td>
                            <td>
                                <span class="badge badge-{{ $article->status === 'published' ? 'success' : ($article->status === 'draft' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($article->status) }}
                                </span>
                            </td>
                            <td>{{ $article->published_at ? $article->published_at->format('d M Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-4">No articles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .magazine-dashboard {
        width: 100%;
        max-width: 100%;
        padding-bottom: 30px;
        overflow-x: hidden;
    }

    .dashboard-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-left: 39px;
        margin-right: 39px;
        min-height: 168px;
        padding: 30px 34px;
        border-radius: 8px;
        background: #8e2d26;
        color: #fff;
        box-shadow: 0 18px 40px rgba(43, 28, 25, .12);
    }

    .dashboard-hero > div {
        min-width: 0;
    }

    .dashboard-hero .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 156px;
        min-height: 48px;
        padding: 12px 18px;
        border: 0;
        border-radius: 8px;
        color: #111827;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 14px 30px rgba(31, 24, 22, .16);
    }

    .dashboard-eyebrow {
        margin: 0 0 8px;
        color: rgba(255, 255, 255, .78);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .dashboard-hero h1 {
        margin: 0;
        color: #fff;
        font-size: 30px;
        font-weight: 800;
    }

    .dashboard-copy {
        margin: 8px 0 0;
        max-width: 680px;
        color: rgba(255, 255, 255, .82);
        font-size: 15px;
        line-height: 1.6;
    }

    .magazine-dashboard > .row {
        margin-left: 29px;
        margin-right: 29px;
    }

    .magazine-dashboard > .row > [class*="col-"] {
        padding-left: 10px;
        padding-right: 10px;
    }

    .dashboard-stat-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 132px;
        padding: 22px;
        border: 1px solid #ece7e4;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(29, 25, 23, .06);
    }

    .dashboard-stat-card p {
        margin: 0 0 8px;
        color: #716761;
        font-size: 13px;
        font-weight: 800;
    }

    .dashboard-stat-card h2 {
        margin: 0;
        color: #201918;
        font-size: 32px;
        font-weight: 800;
    }

    .dashboard-stat-card span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 8px;
        font-size: 20px;
    }

    .dashboard-stat-primary span,
    .dashboard-stat-info span {
        background: #eef4ff;
        color: #2563eb;
    }

    .dashboard-stat-success span {
        background: #edfdf3;
        color: #16a34a;
    }

    .dashboard-stat-warning span {
        background: #fff7e6;
        color: #d97706;
    }

    .dashboard-stat-danger span {
        background: #fff1ef;
        color: #8e2d26;
    }

    .dashboard-stat-secondary span {
        background: #f4f5f7;
        color: #4b5563;
    }

    .dashboard-card {
        border: 1px solid #ece7e4;
        border-radius: 8px;
        box-shadow: 0 10px 28px rgba(29, 25, 23, .05);
    }

    .dashboard-card .card-header {
        background: #fff;
        border-bottom: 1px solid #f0ece9;
    }

    .chart-box {
        min-height: 320px;
    }

    .status-list {
        display: grid;
        gap: 14px;
    }

    .status-list div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid #f0ece9;
    }

    .status-list div:last-child {
        border-bottom: 0;
    }

    .status-list span {
        color: #716761;
        font-weight: 700;
    }

    .status-list strong {
        color: #201918;
        font-size: 20px;
        font-weight: 800;
    }

    .dashboard-table thead th {
        border-top: 0;
        background: #fbfaf8;
        color: #4d4540;
        font-size: 12px;
        text-transform: uppercase;
    }

    .dashboard-table td,
    .dashboard-table th {
        vertical-align: middle;
    }

    @media (max-width: 767.98px) {
        .dashboard-hero {
            align-items: flex-start;
            flex-direction: column;
            margin-left: 0;
            margin-right: 0;
        }

        .magazine-dashboard > .row {
            margin-left: -10px;
            margin-right: -10px;
        }
    }

    @media (min-width: 992px) {
        body.sidebar-fixed .magazine-dashboard {
            margin-left: 20px;
            width: calc(100% - 280px);
        }
    }

    @media (min-width: 992px) and (max-width: 1366px) {
        body.sidebar-fixed .magazine-dashboard {
            margin-left: 270px;
            width: calc(100% - 270px);
        }

        .dashboard-hero {
            padding: 26px 28px;
        }
    }

    @media (max-width: 991.98px) {
        .dashboard-hero {
            min-height: auto;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartElement = document.getElementById('articleChart');

    if (!chartElement || typeof Chart === 'undefined') {
        return;
    }

    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Published articles',
                data: @json($chartData),
                borderColor: '#8e2d26',
                backgroundColor: 'rgba(142, 45, 38, .08)',
                borderWidth: 3,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0
                    }
                }]
            }
        }
    });
});
</script>
@endsection
