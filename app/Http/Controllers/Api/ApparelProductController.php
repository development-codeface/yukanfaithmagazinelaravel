<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApparelProductResource;
use App\Models\ApparelProduct;
use Illuminate\Http\Request;

class ApparelProductController extends Controller
{
    public function index(Request $request)
    {
        $query = ApparelProduct::where('status', 'published')
            ->with(['category', 'primaryImage', 'images', 'variants.images', 'variants.optionValues.option']);

        if ($request->filled('category_id')) {
            $query->where('apparel_category_id', $request->category_id);
        }

        if ($request->filled('category_slug')) {
            $query->whereHas('category', fn ($query) => $query->where('slug', $request->category_slug));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $sortBy = in_array($request->get('sort_by'), ['name', 'price', 'created_at', 'sort_order'], true)
            ? $request->get('sort_by')
            : 'sort_order';
        $sortOrder = $request->get('sort_order') === 'desc' ? 'desc' : 'asc';
        $perPage = min((int) $request->get('per_page', 12), 50);

        return ApparelProductResource::collection(
            $query->orderBy($sortBy, $sortOrder)->latest()->paginate($perPage)
        );
    }
}
