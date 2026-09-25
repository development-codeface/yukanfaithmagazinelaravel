<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\banner;

class BannerController extends Controller
{
    //
    public function index()
    {
        $data['banners'] = Banner::get();
        return view('admin.banner.index', $data);
    }

    public function create()
    {
        return view('admin.banner.create');
    }

    public function store(Request $request)
    {

        // Validate and store the banner
        $request->validate([
            'image' => 'required|image|max:2048', // Example validation rule
        ]);

        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        // destination path
        $destinationPath = public_path('uploads/banners');

        // create folder if not exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // move file
        $image->move($destinationPath, $imageName);

        // save correct path in DB
        Banner::create([
            'image'  => 'uploads/banners/' . $imageName,
            'status' => $request->status ?? 1,
        ]);


        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    public function statusChange($id)
    {
        $banner = Banner::findOrFail($id);
        if ($banner->status == 1) {
            $banner->status = 0;
        } else {
            $banner->status = 1;
        }
        $banner->save();

        return redirect()->route('admin.banners.index')->with('success', 'Banner status updated successfully.');
    }

    public function edit($id)
    {
        $data['banner'] = Banner::findOrFail($id);
        return view('admin.banner.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        // Validate and update the banner
        $request->validate([
            'image' => 'sometimes|image|max:2048', // Example validation rule
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            // destination path
            $destinationPath = public_path('uploads/banners');

            // create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // move file
            $image->move($destinationPath, $imageName);

            // update image path in DB
            $banner->image = 'uploads/banners/' . $imageName;
        }else{
            // If no new image is uploaded, retain the existing image path
            $banner->image = $banner->image;
        }

        $banner->status = $request->status ?? $banner->status;
        $banner->save();

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted successfully.');
    }
}
