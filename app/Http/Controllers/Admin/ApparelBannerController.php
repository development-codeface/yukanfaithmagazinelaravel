<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApparelBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApparelBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:article_access');
    }

    public function index()
    {
        $ads = ApparelBanner::orderBy('sort_order')->latest('id')->paginate(20);
        return view('admin.apparel_banners.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.apparel_banners.form', ['apparelBanner' => new ApparelBanner(['status' => true])]);
    }

    public function store(Request $request)
    {
        ApparelBanner::create($this->validatedData($request));
        return redirect()->route('admin.apparel-banners.index')->with('success', 'Apparel banner created successfully.');
    }

    public function edit(ApparelBanner $apparelBanner)
    {
        return view('admin.apparel_banners.form', compact('apparelBanner'));
    }

    public function update(Request $request, ApparelBanner $apparelBanner)
    {
        $apparelBanner->update($this->validatedData($request, false));
        return redirect()->route('admin.apparel-banners.index')->with('success', 'Apparel banner updated successfully.');
    }

    public function destroy(ApparelBanner $apparelBanner)
    {
        $apparelBanner->delete();
        return redirect()->route('admin.apparel-banners.index')->with('success', 'Apparel banner deleted successfully.');
    }

    private function validatedData(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'link' => 'nullable|url:http,https|max:2048',
            'image' => ($creating ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png,webp|extensions:jpg,jpeg,png,webp|max:4096',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer|min:0|max:99999',
        ]);
        unset($data['image']);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = Str::uuid().'.'.$file->extension();
            $directory = public_path('uploads/apparel-banners');
            if (!is_dir($directory)) mkdir($directory, 0755, true);
            $file->move($directory, $name);
            $data['image'] = 'uploads/apparel-banners/'.$name;
        }
        return $data;
    }
}
