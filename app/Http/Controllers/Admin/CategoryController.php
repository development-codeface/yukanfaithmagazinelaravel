<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerSlider;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_subtitle' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'subtitle_description' => 'nullable|string',

            'banner_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

            'slider_image' => 'nullable|array',
            'slider_image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'slider_link' => 'nullable|array',
            'slider_link.*' => 'nullable|url|max:255',
        ]);

        $image = $request->file('banner_image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        // destination path
        $destinationPath = public_path('uploads/category/banner_image');

        // create folder if not exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // move file
        $image->move($destinationPath, $imageName);

        $category = Category::create([
            'category_name' => $request->category_name,
            'category_subtitle' => $request->category_subtitle,
            'description' => $request->description,
            'subtitle_description' => $request->subtitle_description,
           'banner_image' => 'uploads/category/banner_image/' . $imageName,
        ]);


        // $sliderImages = [];

        if ($request->hasFile('slider_image')) {

            foreach ($request->file('slider_image') as $key => $image) {

                if (!$image) {
                    continue; // skip empty rows
                }

                $imageName = 'slider_' . time() . '_' . $key . '.' . $image->getClientOriginalExtension();

                $destinationPath = public_path('uploads/category/banner_sliders');
                

                // create folder if not exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // move image
                $image->move($destinationPath, $imageName);

                BannerSlider::create([
                    'category_id' => $category->id,
                    'image' => 'uploads/category/banner_sliders/' . $imageName,
                    'link'  => $request->slider_link[$key] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function edit(Category $category)
    {
        $category->load('bannerSliders'); // relationship
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
          $request->validate([
            'category_name' => 'required|string|max:255',
            'category_subtitle' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'subtitle_description' => 'nullable|string',

            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'banner_slider_id' => 'nullable|array',
            'banner_slider_id.*' => 'nullable|integer|exists:banner_sliders,id',
            'slider_image' => 'nullable|array',
            'slider_image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'slider_link' => 'nullable|array',
            'slider_link.*' => 'nullable|url|max:255',
        ]);
    

        // ✅ Update text fields
        $category->update([
            'category_name'        => $request->category_name,
            'category_subtitle'    => $request->category_subtitle,
            'description'          => $request->description,
            'subtitle_description' => $request->subtitle_description,
        ]);

        // ✅ Update banner image (ONLY if changed)
        if ($request->hasFile('banner_image')) {
            $image = $request->file('banner_image');
            $imageName = time() . '.' . $image->extension();
            $destinationPath = public_path('uploads/category/banner_image');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $image->move($destinationPath, $imageName);

            $category->update([
               // 'banner_image' => 'images/category/banner_image/' . $imageName,
               'banner_image' => 'uploads/category/banner_image/' . $imageName,
            ]);
        }

        $sliderIds = $request->input('banner_slider_id', []);
        $images    = $request->file('slider_image', []);
        $links     = $request->input('slider_link', []);

        $submittedSliderIds = array_filter($sliderIds);
        $category->bannerSliders()
            ->when(!empty($submittedSliderIds), fn ($query) => $query->whereNotIn('id', $submittedSliderIds))
            ->delete();

        $rowCount = max(count($sliderIds), count($images), count($links));

        for ($key = 0; $key < $rowCount; $key++) {
            $sliderId = $sliderIds[$key] ?? null;
            $image = $images[$key] ?? null;
            $link = $links[$key] ?? null;

            if ($sliderId) {
                $slider = BannerSlider::where('category_id', $category->id)->find($sliderId);

                if (!$slider) {
                    continue;
                }

                $slider->update([
                    'link' => $link,
                ]);
            } elseif ($image && $image->isValid()) {
                $slider = BannerSlider::create([
                    'category_id' => $category->id,
                    'image' => '',
                    'link' => $link,
                ]);
            } else {
                continue;
            }

            if (!$image || !$image->isValid()) {
                continue;
            }

            $destinationPath = public_path('uploads/category/banner_sliders');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $sliderName = 'slider_' . time() . '_' . $key . '.' . $image->extension();
            $image->move($destinationPath, $sliderName);

            $slider->update([
                'image' => 'uploads/category/banner_sliders/' . $sliderName,
            ]);
        }


        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully');
    }


    public function destroy(Category $category)
    {
        Category::destroy($category->id);
        BannerSlider::where('category_id', $category->id)->delete();
        return back()->with('success', 'Category deleted successfully');
    }
}
