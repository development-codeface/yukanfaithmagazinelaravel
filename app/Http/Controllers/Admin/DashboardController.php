<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\MagazineIssue;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
   public function index(Request $request)
    {
        $monthStart = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))->map(fn ($offset) => $monthStart->copy()->addMonths($offset));

        $publishedByMonth = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $monthStart)
            ->selectRaw("DATE_FORMAT(published_at, '%Y-%m') as month_key, COUNT(*) as total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $activePlansQuery = Plan::query();

        if (Schema::hasColumn('plans', 'status')) {
            $activePlansQuery->where('status', true);
        } elseif (Schema::hasColumn('plans', 'is_active')) {
            $activePlansQuery->where('is_active', true);
        }

        $dashboardStats = [
            'articles' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'drafts' => Article::where('status', 'draft')->count(),
            'archived' => Article::where('status', 'archived')->count(),
            'authors' => Article::whereNotNull('author_id')->distinct('author_id')->count('author_id'),
            'readers' => User::whereDoesntHave('roles')->count(),
            'categories' => Category::count(),
            'issues' => MagazineIssue::count(),
            'activeSubscriptions' => Subscription::where('status', 'active')->count(),
            'activePlans' => $activePlansQuery->count(),
        ];

        $chartLabels = $months->map(fn (Carbon $month) => $month->format('M Y'))->values();
        $chartData = $months
            ->map(fn (Carbon $month) => (int) ($publishedByMonth[$month->format('Y-m')] ?? 0))
            ->values();

        $categoryOverview = Category::query()
            ->leftJoin('articles', 'categories.id', '=', 'articles.category_id')
            ->select('categories.id', 'categories.category_name')
            ->selectRaw('COUNT(articles.id) as articles_count')
            ->selectRaw("SUM(CASE WHEN articles.status = 'published' THEN 1 ELSE 0 END) as published_count")
            ->groupBy('categories.id', 'categories.category_name')
            ->orderByDesc('articles_count')
            ->limit(8)
            ->get();

        $recentArticles = Article::with(['author', 'category'])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard_dynamic', compact(
            'dashboardStats',
            'chartLabels',
            'chartData',
            'categoryOverview',
            'recentArticles'
        ));
    }
}
