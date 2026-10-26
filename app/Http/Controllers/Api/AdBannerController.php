<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBanner;

class AdBannerController extends Controller
{
    public function index()
    {
        return response()->json(['data' => AdBanner::active()->get()->map(fn (AdBanner $ad) => [
            'id' => $ad->id,
            'title' => $ad->title,
            'image_url' => asset($ad->image),
            'link' => $ad->link,
            'status' => $ad->status,
        ])]);
    }
}
