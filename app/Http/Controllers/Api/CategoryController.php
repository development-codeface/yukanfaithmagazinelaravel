<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of all categories.
     */
    public function index(Request $request)
    {
        $query = Category::with(['bannerSliders', 'subcategories']);

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('category_name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        $perPage = $request->get('per_page', 50);
        $categories = $query->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    /**
     * Display a specific category.
     */
    public function show(Category $category)
    {
        $category->load(['bannerSliders', 'subcategories']);
        $category->setRelation('publishedArticles', $this->categoryArticlesQuery($category)->paginate(10));

        return new CategoryResource($category);
    }

    /**
     * Get all categories (simple list without pagination for mobile dropdowns).
     */
    public function all()
    {
        $categories = Category::with(['bannerSliders', 'subcategories'])->get();
        return CategoryResource::collection($categories);
    }

    private function categoryArticlesQuery(Category $category)
    {
        return Article::where('status', 'published')
            ->where(function ($query) use ($category) {
                $query->where('category_id', $category->id)
                    ->orWhereHas('categories', function ($query) use ($category) {
                        $query->where('categories.id', $category->id);
                    });
            })
            ->orderByRaw("CAST(REGEXP_SUBSTR(title, '[12][0-9]{3}') AS UNSIGNED) DESC")
            ->orderByRaw("
                CASE
                    WHEN LOWER(title) REGEXP '(^|[^a-z])dec(ember)?([^a-z]|$)' THEN 12
                    WHEN LOWER(title) REGEXP '(^|[^a-z])nov(ember)?([^a-z]|$)' THEN 11
                    WHEN LOWER(title) REGEXP '(^|[^a-z])oct(ober)?([^a-z]|$)' THEN 10
                    WHEN LOWER(title) REGEXP '(^|[^a-z])sep(t|tember)?([^a-z]|$)' THEN 9
                    WHEN LOWER(title) REGEXP '(^|[^a-z])aug(ust)?([^a-z]|$)' THEN 8
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jul(y)?([^a-z]|$)' THEN 7
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jun(e)?([^a-z]|$)' THEN 6
                    WHEN LOWER(title) REGEXP '(^|[^a-z])may([^a-z]|$)' THEN 5
                    WHEN LOWER(title) REGEXP '(^|[^a-z])apr(il)?([^a-z]|$)' THEN 4
                    WHEN LOWER(title) REGEXP '(^|[^a-z])mar(ch)?([^a-z]|$)' THEN 3
                    WHEN LOWER(title) REGEXP '(^|[^a-z])feb(ruary)?([^a-z]|$)' THEN 2
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jan(uary)?([^a-z]|$)' THEN 1
                    ELSE 0
                END DESC
            ")
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc');
    }
}
