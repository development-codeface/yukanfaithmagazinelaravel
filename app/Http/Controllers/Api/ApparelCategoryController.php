<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApparelCategoryResource;
use App\Models\ApparelCategory;
use Illuminate\Http\Request;

class ApparelCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ApparelCategory::where('status', true)->withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return ApparelCategoryResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->get()
        );
    }
}
