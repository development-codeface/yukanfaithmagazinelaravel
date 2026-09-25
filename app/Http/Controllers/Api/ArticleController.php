<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of published articles with pagination.
     */
    public function index(Request $request)
    {
        $query = Article::where('status', 'published');

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $this->applyCategoryFilter($query, $request->category_id);
        }

        // Filter by free/paid article type
        if ($request->has('access_type') && in_array($request->access_type, ['free', 'paid'], true)) {
            $query->where('access_type', $request->access_type);
        }

        // Search by title or content
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%$search%")
                    ->orWhere('summary', 'like', "%$search%")
                    ->orWhere('content', 'like', "%$search%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 12);
        $articles = $query->with($this->articleRelations())->paginate($perPage);

        return ArticleResource::collection($articles);
    }

    /**
     * Display the latest published article.
     */
    public function latest()
    {
        $articles = Article::where('status', 'published')
            ->with($this->articleRelations())
            ->latest('published_at')
            ->get();

        return ArticleResource::collection($articles);
    }

    /**
     * Display featured/latest articles.
     */
    public function featured()
    {
        $articles = Article::where('status', 'published')
            ->with($this->articleRelations())
            ->latest('published_at')
            ->take(6)
            ->get();

        return ArticleResource::collection($articles);
    }

    /**
     * Display free published articles.
     */
    public function free(Request $request)
    {
        return $this->byAccessType($request, 'free');
    }

    /**
     * Display paid published articles.
     */
    public function paid(Request $request)
    {
        return $this->byAccessType($request, 'paid');
    }

    /**
     * Display published articles by access type.
     */
    private function byAccessType(Request $request, string $accessType)
    {
        $perPage = $request->get('per_page', 12);

        $articles = Article::where('status', 'published')
            ->where('access_type', $accessType)
            ->with($this->articleRelations())
            ->latest('published_at')
            ->paginate($perPage);

        return ArticleResource::collection($articles);
    }

    /**
     * Display a specific article.
     */
    public function show(Article $article)
    {
        if ($article->status !== 'published') {
            return response()->json([
                'message' => 'Article not found.',
            ], 404);
        }

        if (($article->access_type ?? 'free') === 'paid') {
            return response()->json([
                'message' => 'Please login and use /api/user/articles/'.$article->id.' with an active subscription to read this article.',
            ], 403);
        }

        $article->load($this->articleRelations());
        return new ArticleResource($article);
    }

    /**
     * Search articles by query.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $search = $request->get('q');

        $articles = Article::where('status', 'published')
            ->where(function($query) use ($search) {
                $query->where('title', 'like', "%$search%")
                      ->orWhere('summary', 'like', "%$search%")
                      ->orWhere('content', 'like', "%$search%");
            })
            ->with($this->articleRelations())
            ->latest('published_at')
            ->paginate(10);

        return ArticleResource::collection($articles);
    }

    /**
     * Get articles by category.
     */
    public function byCategory(Request $request, $categoryId)
    {
        $query = Article::where('status', 'published');
        $this->applyCategoryFilter($query, $categoryId);

        $articles = $query
            ->with($this->articleRelations())
            ->latest('published_at')
            ->paginate(10);

        return ArticleResource::collection($articles);
    }

    /**
     * Get article statistics for dashboard.
     */
    public function stats()
    {
        return response()->json([
            'total_articles' => Article::count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'draft_articles' => Article::where('status', 'draft')->count(),
            'latest_article' => new ArticleResource(
                Article::where('status', 'published')
                    ->with($this->articleRelations())
                    ->latest()
                    ->first()
            ),
        ]);
    }

    public function past_magazines(Request $request)
    {
        $query = Article::where('status', 'published');
        $this->applyCategoryFilter($query, 6);

        $articles = $query
            ->with($this->articleRelations())
            ->latest('published_at')
            ->paginate(10);

        return ArticleResource::collection($articles);
    }

    private function applyCategoryFilter($query, $categoryId): void
    {
        $query->where(function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId)
                ->orWhereHas('categories', function ($query) use ($categoryId) {
                    $query->where('categories.id', $categoryId);
                });
        });
    }

    private function articleRelations(): array
    {
        return ['category', 'categories', 'author', 'issue', 'blocks', 'galleryImages'];
    }

    
}
