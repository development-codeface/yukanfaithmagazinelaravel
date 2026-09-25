<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MagazineIssueResource;
use App\Models\MagazineIssue;
use Illuminate\Http\Request;

class MagazineIssueController extends Controller
{
    /**
     * Display a listing of all magazine issues with pagination.
     */
    public function index(Request $request)
    {
        $query = MagazineIssue::query();

        // Search by title
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 12);
        $issues = $query->paginate($perPage);

        return MagazineIssueResource::collection($issues);
    }

    /**
     * Display latest magazine issues.
     */
    public function latest()
    {
        $issues = MagazineIssue::latest('published_at')
            ->take(8)
            ->get();

        return MagazineIssueResource::collection($issues);
    }

    /**
     * Display a specific magazine issue with articles.
     */
    public function show(MagazineIssue $magazine_issue)
    {
        $magazine_issue->load([
            'articles' => function ($query) {
                $query->where('status', 'published')
                    ->with(['category', 'categories', 'author', 'issue', 'blocks', 'galleryImages'])
                    ->latest('published_at');
            },
        ]);

        return new MagazineIssueResource($magazine_issue);
    }

    /**
     * Search magazine issues by query.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $search = $request->get('q');

        $issues = MagazineIssue::where('title', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->latest('published_at')
            ->paginate(10);

        return MagazineIssueResource::collection($issues);
    }

    /**
     * Get magazine statistics for dashboard.
     */
    public function stats()
    {
        return response()->json([
            'total_issues' => MagazineIssue::count(),
            'latest_issue' => new MagazineIssueResource(MagazineIssue::latest()->first()),
            'total_articles' => MagazineIssue::withCount('articles')
                ->latest()
                ->first()
                ?->articles_count ?? 0,
        ]);
    }
}
