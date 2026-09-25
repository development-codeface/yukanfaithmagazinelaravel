<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\banner as Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display active banners.
     */
    public function index(Request $request)
    {
        $query = Banner::where('status', 1);

        $perPage = $request->get('per_page', 10);
        $banners = $query->latest()->paginate($perPage);

        return BannerResource::collection($banners);
    }

    /**
     * Get all active banners (simple list for frontend display).
     */
    public function active()
    {
        $banners = Banner::where('status', 1)
            ->latest()
            ->get();

        return BannerResource::collection($banners);
    }

    /**
     * Display a specific banner.
     */
    public function show(Banner $banner)
    {
        if ($banner->status != 1) {
            return response()->json([
                'message' => 'Banner not found.',
            ], 404);
        }

        return new BannerResource($banner);
    }
}
