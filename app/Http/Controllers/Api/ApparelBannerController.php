<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApparelBanner;

class ApparelBannerController extends Controller
{
    public function index()
    {
        return response()->json(['data' => ApparelBanner::active()->get()->map(fn (ApparelBanner $ad) => [
            'id' => $ad->id,
            'title' => $ad->title,
            'image_url' => asset($ad->image),
            'link' => $ad->link,
            'status' => $ad->status,
            'sort_order' => $ad->sort_order,
        ])]);
    }
}
