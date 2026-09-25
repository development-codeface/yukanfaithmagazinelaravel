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
            'description' => 'nullable|string',
        ]);

        MagazineIssue::create($data);

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
            'description' => 'nullable|string',
        ]);

        $magazineIssue->update($data);

        return redirect()->route('admin.magazine-issues.index')
            ->with('success', 'Issue updated successfully');
    }

    public function destroy(MagazineIssue $magazineIssue)
    {
        $magazineIssue->delete();
        return back()->with('success', 'Issue deleted');
    }
}