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
            'digital_price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'print_price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'both_price' => 'nullable|numeric|min:0.01|max:99999999.99',
        ]);

        $data = $this->pricePayload($data);
        $this->validateReader($data);
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
            'published_at' => 'nullable|date',
            'description' => 'nullable|string',
            'pdf_url' => 'nullable|url:http,https|max:255',
            'price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'digital_price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'print_price' => 'nullable|numeric|min:0.01|max:99999999.99',
            'both_price' => 'nullable|numeric|min:0.01|max:99999999.99',
        ]);

        $data = $this->pricePayload($data);
        $this->validateReader($data, $magazineIssue->pdf_url);
        $magazineIssue->update($data);

        return redirect()->route('admin.magazine-issues.index')
            ->with('success', 'Issue updated successfully');
    }

    public function destroy(MagazineIssue $magazineIssue)
    {
        $magazineIssue->delete();
        return back()->with('success', 'Issue deleted');
    }

    private function pricePayload(array $data): array
    {
        if (array_intersect(['digital_price', 'print_price', 'both_price'], array_keys($data))) {
            $prices = array_filter([$data['digital_price'] ?? null, $data['print_price'] ?? null, $data['both_price'] ?? null], fn ($price) => $price !== null);
            $data['price'] = $prices ? min($prices) : null;
        } elseif (array_key_exists('price', $data)) {
            $data['digital_price'] = $data['price'];
        }
        return $data;
    }

    private function validateReader(array $data, ?string $existingUrl = null): void
    {
        $url = array_key_exists('pdf_url', $data) ? $data['pdf_url'] : $existingUrl;
        if ((isset($data['digital_price']) || isset($data['both_price'])) && !$url) {
            throw \Illuminate\Validation\ValidationException::withMessages(['pdf_url' => 'An external reader URL is required for Digital or Digital + Print pricing.']);
        }
    }
}
