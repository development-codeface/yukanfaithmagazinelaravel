<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MagazineIssue;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MagazineIssueController extends Controller
{
    public function index()
    {
        $issues = MagazineIssue::latest()->get();
        return view('admin.magazine_issues.index', compact('issues'));
    }

    public function create()
    {
        return view('admin.magazine_issues.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'issue_date'  => 'nullable|date',
            'published_at' => 'nullable|date',
            'description' => 'nullable|string',
            'pdf_url' => 'nullable|url:http,https|max:255',
            'price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'integer|exists:articles,id',
        ]);

        $issue = MagazineIssue::create(collect($data)->except('article_ids')->all());
        \App\Models\Article::whereIn('id', $data['article_ids'] ?? [])->update(['issue_id' => $issue->id]);

        return redirect()->route('admin.magazine-issues.index')
            ->with('success', 'Issue created successfully');
    }

    public function edit(MagazineIssue $magazineIssue)
    {
        return view('admin.magazine_issues.edit', compact('magazineIssue'));
    }

    public function update(Request $request, MagazineIssue $magazineIssue)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'issue_date'  => 'nullable|date',
            'published_at' => 'nullable|date',
            'description' => 'nullable|string',
            'pdf_url' => 'nullable|url:http,https|max:255',
            'price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'integer|exists:articles,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($magazineIssue, $data) {
            $magazineIssue->update(collect($data)->except('article_ids')->all());
            $magazineIssue->articles()->update(['issue_id' => null]);
            \App\Models\Article::whereIn('id', $data['article_ids'] ?? [])->update(['issue_id' => $magazineIssue->id]);
        });

        return redirect()->route('admin.magazine-issues.index')
            ->with('success', 'Issue updated successfully');
    }

    public function destroy(MagazineIssue $magazineIssue)
    {
        $magazineIssue->delete();
        return back()->with('success', 'Issue deleted');
    }
}
