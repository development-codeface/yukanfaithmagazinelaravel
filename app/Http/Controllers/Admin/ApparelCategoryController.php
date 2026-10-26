<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApparelCategory;
use Illuminate\Http\Request;

class ApparelCategoryController extends Controller
{
    public function index()
    {
        $categories = ApparelCategory::orderBy('sort_order')->latest()->get();

        return view('admin.apparel_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.apparel_categories.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = ApparelCategory::makeUniqueSlug($data['name']);
        $data['status'] = $request->boolean('status');
        $data['image'] = $this->uploadImage($request);

        ApparelCategory::create($data);

        return redirect()->route('admin.apparel-categories.index')
            ->with('success', 'Apparel category created successfully.');
    }

    public function edit(ApparelCategory $apparelCategory)
    {
        return view('admin.apparel_categories.edit', compact('apparelCategory'));
    }

    public function update(Request $request, ApparelCategory $apparelCategory)
    {
        $data = $this->validatedData($request);
        $data['slug'] = ApparelCategory::makeUniqueSlug($data['name'], $apparelCategory->id);
        $data['status'] = $request->boolean('status');

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request);
        }

        $apparelCategory->update($data);

        return redirect()->route('admin.apparel-categories.index')
            ->with('success', 'Apparel category updated successfully.');
    }

    public function destroy(ApparelCategory $apparelCategory)
    {
        $apparelCategory->delete();

        return back()->with('success', 'Apparel category deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function uploadImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $image = $request->file('image');
        $name = time() . '_' . $image->getClientOriginalName();
        $path = public_path('uploads/apparel/categories');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $name);

        return 'uploads/apparel/categories/' . $name;
    }
}
