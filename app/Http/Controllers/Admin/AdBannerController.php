<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdBannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:article_access');
    }

    public function index()
    {
        $ads = AdBanner::latest('id')->paginate(20);
        return view('admin.ad_banners.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.ad_banners.form', ['adBanner' => new AdBanner(['status' => true])]);
    }

    public function store(Request $request)
    {
        AdBanner::create($this->validatedData($request));
        return redirect()->route('admin.ad-banners.index')->with('success', 'Ads banner created successfully.');
    }

    public function edit(AdBanner $adBanner)
    {
        return view('admin.ad_banners.form', compact('adBanner'));
    }

    public function update(Request $request, AdBanner $adBanner)
    {
        $adBanner->update($this->validatedData($request, false));
        return redirect()->route('admin.ad-banners.index')->with('success', 'Ads banner updated successfully.');
    }

    public function destroy(AdBanner $adBanner)
    {
        $adBanner->delete();
        return redirect()->route('admin.ad-banners.index')->with('success', 'Ads banner deleted successfully.');
    }

    private function validatedData(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'link' => 'required|url:http,https|max:2048',
            'image' => ($creating ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png,webp|max:4096',
            'status' => 'required|boolean',
        ]);
        unset($data['image']);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = Str::uuid().'.'.$file->extension();
            $file->move(public_path('uploads/ads'), $name);
            $data['image'] = 'uploads/ads/'.$name;
        }
        return $data;
    }
}
