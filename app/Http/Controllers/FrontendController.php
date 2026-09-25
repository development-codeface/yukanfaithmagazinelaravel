<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;

class FrontendController extends Controller
{

    public function home(Request $request)
    {
        $articleSearch = trim((string) $request->query('search', ''));

        // Latest featured articles
        $latestArticles = Article::where('status','published')
            ->when($articleSearch !== '', function ($query) use ($articleSearch) {
                $query->where(function ($query) use ($articleSearch) {
                    $query->where('title', 'like', "%{$articleSearch}%")
                        ->orWhere('summary', 'like', "%{$articleSearch}%")
                        ->orWhere('content', 'like', "%{$articleSearch}%")
                        ->orWhereHas('category', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        })
                        ->orWhereHas('categories', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        });
                });
            })
            ->orderByIssueTitleDate()
            ->take(6)
            ->get();

        $banners = \App\Models\Banner::where('status',1)
            ->latest()
            ->get();

        $pastMagazineCategory = Category::query()
            ->whereRaw("REPLACE(REPLACE(LOWER(TRIM(category_name)), '-', ' '), '_', ' ') IN (?, ?)", [
                'past magazines',
                'past magazine',
            ])
            ->first();

        $pastIssues = Article::where('status','published')
            ->when($pastMagazineCategory, function ($query) use ($pastMagazineCategory) {
                $query->where(function ($query) use ($pastMagazineCategory) {
                    $query->where('category_id', $pastMagazineCategory->id)
                        ->orWhereHas('categories', function ($query) use ($pastMagazineCategory) {
                            $query->where('categories.id', $pastMagazineCategory->id);
                        });
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($articleSearch !== '', function ($query) use ($articleSearch) {
                $query->where(function ($query) use ($articleSearch) {
                    $query->where('title', 'like', "%{$articleSearch}%")
                        ->orWhere('summary', 'like', "%{$articleSearch}%")
                        ->orWhere('content', 'like', "%{$articleSearch}%")
                        ->orWhereHas('category', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        })
                        ->orWhereHas('categories', function ($query) use ($articleSearch) {
                            $query->where('category_name', 'like', "%{$articleSearch}%");
                        });
                });
            })
            ->orderByIssueTitleDate()
            ->get();

        $events = \App\Models\Event::where('status',1)
            ->latest()
            ->get();

        return view('frontend.home',
            compact('latestArticles','pastIssues','banners','events','articleSearch')
        );
    }


    // ================= CATEGORY PAGE =================
    public function category(Category $category)
    {
        $category->load('bannerSliders');

        // 🔥 IMPORTANT CHANGE HERE
        $articles = Article::where('status','published')
            ->where(function ($query) use ($category) {
                $query->where('category_id', $category->id)
                    ->orWhereHas('categories', function($q) use ($category){
                        $q->where('categories.id', $category->id);
                    });
            })
            ->orderByIssueTitleDate()
            ->paginate(10);

        return view('frontend.category', compact('category','articles'));
    }


    // ================= ARTICLE DETAILS =================
    public function article(Article $article)
    {
        abort_if($article->status !== 'published', 404);

        if (($article->access_type ?? 'free') === 'paid') {
            if (!auth()->check()) {
                return redirect()->route('login')
                    ->with('error', 'Please login to read paid articles.');
            }

            if (!auth()->user()->canReadArticle($article)) {
                return redirect()->route('user.dashboard')
                    ->with('error', 'Please subscribe or buy this article to read it.');
            }
        }

        return view('frontend.article', compact('article'));
    }

    // ================= EVENT DETAILS =================
    public function event($id)
    {
        $event = \App\Models\Event::findOrFail($id);

        return view('frontend.event', compact('event'));
    }

}
