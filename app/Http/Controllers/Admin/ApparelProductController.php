<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApparelCategory;
use App\Models\ApparelProductOptionValue;
use App\Models\ApparelProduct;
use App\Models\ApparelProductImage;
use Illuminate\Http\Request;

class ApparelProductController extends Controller
{
    public function index()
    {
        $query = ApparelProduct::with(['category', 'primaryImage']);

        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (request()->filled('category_id')) {
            $query->where('apparel_category_id', request('category_id'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('admin.apparel_products.index', [
            'products' => $query->latest()->paginate(15)->withQueryString(),
            'categories' => ApparelCategory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.apparel_products.create', [
            'categories' => ApparelCategory::where('status', true)->orderBy('name')->pluck('name', 'id'),
            'product' => new ApparelProduct(['status' => 'draft']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = ApparelProduct::makeUniqueSlug($data['name']);
        $data['is_featured'] = $request->boolean('is_featured');

        $product = ApparelProduct::create($data);

        $this->syncVariants($request, $product);
        $this->storeImages($request, $product);

        return redirect()->route('admin.apparel-products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(ApparelProduct $apparelProduct)
    {
        $apparelProduct->load(['images', 'options.values', 'variants.images', 'variants.optionValues.option']);

        return view('admin.apparel_products.edit', [
            'product' => $apparelProduct,
            'categories' => ApparelCategory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, ApparelProduct $apparelProduct)
    {
        $data = $this->validatedData($request, $apparelProduct);
        $data['slug'] = ApparelProduct::makeUniqueSlug($data['name'], $apparelProduct->id);
        $data['is_featured'] = $request->boolean('is_featured');

        $apparelProduct->update($data);

        $this->syncVariants($request, $apparelProduct);
        $this->deleteRemovedImages($request, $apparelProduct);
        $this->storeImages($request, $apparelProduct);

        return redirect()->route('admin.apparel-products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(ApparelProduct $apparelProduct)
    {
        $apparelProduct->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function validatedData(Request $request, ?ApparelProduct $product = null): array
    {
        $data = $request->validate([
            'size_chart_columns' => 'nullable|string|max:1000',
            'size_chart_rows' => 'nullable|array|max:50',
            'size_chart_rows.*' => 'array:label,imperial,metric',
            'size_chart_rows.*.label' => 'required|string|max:150',
            'size_chart_rows.*.imperial' => 'required|string|max:2000',
            'size_chart_rows.*.metric' => 'required|string|max:2000',
            'content_editor' => 'nullable|in:1',
            'detail_sections' => 'nullable|array|max:50',
            'detail_sections.*' => 'array:icon,title,content,icon_upload,icon_image_path,remove_icon_image',
            'detail_sections.*.icon' => 'required|in:info,ruler,shirt,leaf,shield,truck,return,wash,star,heart',
            'detail_sections.*.title' => 'required|string|max:255',
            'detail_sections.*.content' => 'required|string|max:10000',
            'detail_sections.*.icon_upload' => ['bail', 'nullable', 'image:allow_svg', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048', new \App\Rules\SafeSvgIcon],
            'detail_sections.*.icon_image_path' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_column($product?->detail_sections ?? [], 'icon_image_path'))],
            'detail_sections.*.remove_icon_image' => 'nullable|boolean',
            'apparel_category_id' => 'required|integer|exists:apparel_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,published',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'size_guide' => 'nullable|string',
            'product_details' => 'nullable|string',
            'care_instructions' => 'nullable|string',
            'shipping_delivery' => 'nullable|string',
            'return_policy' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:apparel_product_images,id',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer|exists:apparel_product_options,id',
            'options.*.name' => 'nullable|string|max:100',
            'options.*.type' => 'nullable|string|max:100',
            'options.*.values' => 'nullable|array',
            'options.*.values.*.id' => 'nullable|integer|exists:apparel_product_option_values,id',
            'options.*.values.*.value' => 'nullable|string|max:255',
            'options.*.values.*.color_hex' => 'nullable|string|max:20',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:apparel_product_variants,id',
            'variants.*.option_type' => 'nullable|string|max:100',
            'variants.*.option_value' => 'nullable|string|max:255',
            'variants.*.color_name' => 'nullable|string|max:100',
            'variants.*.color_hex' => 'nullable|string|max:20',
            'variants.*.size' => 'nullable|string|max:50',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.status' => 'nullable|boolean',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'variants.*.option_value_ids' => 'nullable|string|max:500',
            'remove_variant_images' => 'nullable|array',
            'remove_variant_images.*' => 'integer|exists:apparel_product_images,id',
        ]);

        if ($request->input('content_editor') === '1') {
            $columns = array_map('trim', str_getcsv($data['size_chart_columns'] ?? '', ',', '"', ''));
            $rows = $data['size_chart_rows'] ?? [];
            if ($rows && (!$columns[0] || count($columns) > 30 || in_array('', $columns, true))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['size_chart_columns' => 'Enter between 1 and 30 size names, separated by commas.']);
            }
            $chartRows = [];
            foreach ($rows as $index => $row) {
                $imperial = array_map('trim', str_getcsv($row['imperial'], ',', '"', ''));
                $metric = array_map('trim', str_getcsv($row['metric'], ',', '"', ''));
                if (count($imperial) !== count($columns) || count($metric) !== count($columns)
                    || in_array('', $imperial, true) || in_array('', $metric, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['size_chart_rows.'.$index.'.imperial' => 'Enter one imperial and one metric value per size column, separated by commas.']);
                }
                $chartRows[] = ['label' => $row['label'], 'imperial' => $imperial, 'metric' => $metric];
            }
            $data['size_chart'] = $chartRows ? ['columns' => $columns, 'rows' => $chartRows] : null;
            $data['detail_sections'] = array_values($data['detail_sections'] ?? []);
        }
        // Retained paths must belong to this product; uploaded files get server-generated names.
        foreach ($data['detail_sections'] ?? [] as $index => $detail) {
            $imagePath = !empty($detail['remove_icon_image']) ? null : ($detail['icon_image_path'] ?? null);
            if (!empty($detail['icon_upload'])) {
                $directory = public_path('uploads/apparel/detail-icons');
                \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
                $filename = \Illuminate\Support\Str::uuid().'.'.$detail['icon_upload']->extension();
                $detail['icon_upload']->move($directory, $filename);
                $imagePath = 'uploads/apparel/detail-icons/'.$filename;
            }
            unset($detail['icon_upload'], $detail['icon_image_path'], $detail['remove_icon_image']);
            if ($imagePath) {
                $detail['icon_image_path'] = $imagePath;
            }
            $data['detail_sections'][$index] = $detail;
        }
        unset($data['size_chart_columns'], $data['size_chart_rows'], $data['content_editor']);

        return $data;
    }

    private function syncVariants(Request $request, ApparelProduct $product): void
    {
        $optionValueIdsByKey = $this->syncOptions($request, $product);
        $keptVariantIds = [];

        foreach ($request->input('variants', []) as $index => $variant) {
            $hasValue = collect($variant)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();

            if (!$hasValue) {
                continue;
            }

            $selectedValueIds = $this->variantOptionValueIds($variant, $optionValueIdsByKey);
            $selectedValues = $selectedValueIds
                ? ApparelProductOptionValue::with('option')->whereIn('id', $selectedValueIds)->get()
                : collect();
            $colorValue = $selectedValues->first(fn ($value) => strtolower((string) optional($value->option)->type) === 'color' || strtolower((string) optional($value->option)->name) === 'colour' || strtolower((string) optional($value->option)->name) === 'color');
            $sizeValue = $selectedValues->first(fn ($value) => strtolower((string) optional($value->option)->type) === 'size' || strtolower((string) optional($value->option)->name) === 'size');
            $optionLabel = $selectedValues->map(fn ($value) => $value->value)->implode(' / ');

            $variantModel = $product->variants()->updateOrCreate([
                'id' => $variant['id'] ?? null,
            ], [
                'option_type' => $variant['option_type'] ?? ($selectedValues->isNotEmpty() ? 'generated' : null),
                'option_value' => $optionLabel ?: ($variant['option_value'] ?? null),
                'color_name' => $colorValue->value ?? ($variant['color_name'] ?? null),
                'color_hex' => $colorValue->color_hex ?? ($variant['color_hex'] ?? null),
                'size' => $sizeValue->value ?? ($variant['size'] ?? null),
                'sku' => $variant['sku'] ?? null,
                'price' => $variant['price'] ?? null,
                'stock' => $variant['stock'] ?? 0,
                'status' => !empty($variant['status']),
                'sort_order' => $index,
            ]);

            $keptVariantIds[] = $variantModel->id;
            $variantModel->optionValues()->sync($selectedValueIds);
            $this->storeVariantImages($request, $product, $variantModel, $index);
        }

        $product->variants()
            ->when($keptVariantIds, fn ($query) => $query->whereNotIn('id', $keptVariantIds))
            ->delete();
    }

    private function syncOptions(Request $request, ApparelProduct $product): array
    {
        $keptOptionIds = [];
        $valueIdsByKey = [];

        foreach ($request->input('options', []) as $optionIndex => $option) {
            $name = trim($option['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $optionModel = $product->options()->updateOrCreate([
                'id' => $option['id'] ?? null,
            ], [
                'name' => $name,
                'type' => $option['type'] ?? null,
                'sort_order' => $optionIndex,
            ]);

            $keptOptionIds[] = $optionModel->id;
            $keptValueIds = [];

            foreach ($option['values'] ?? [] as $valueIndex => $value) {
                $label = trim($value['value'] ?? '');

                if ($label === '') {
                    continue;
                }

                $valueModel = $optionModel->values()->updateOrCreate([
                    'id' => $value['id'] ?? null,
                ], [
                    'value' => $label,
                    'color_hex' => $value['color_hex'] ?? null,
                    'sort_order' => $valueIndex,
                ]);

                $keptValueIds[] = $valueModel->id;
                $valueIdsByKey[$optionIndex . ':' . $valueIndex] = $valueModel->id;
                $valueIdsByKey['id:' . $valueModel->id] = $valueModel->id;
            }

            $optionModel->values()
                ->when($keptValueIds, fn ($query) => $query->whereNotIn('id', $keptValueIds))
                ->delete();
        }

        $product->options()
            ->when($keptOptionIds, fn ($query) => $query->whereNotIn('id', $keptOptionIds))
            ->delete();

        return $valueIdsByKey;
    }

    private function variantOptionValueIds(array $variant, array $optionValueIdsByKey): array
    {
        return collect(explode(',', $variant['option_value_ids'] ?? ''))
            ->map(fn ($key) => trim($key))
            ->filter()
            ->map(fn ($key) => $optionValueIdsByKey[$key] ?? (ctype_digit($key) ? (int) $key : null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function storeImages(Request $request, ApparelProduct $product): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $path = public_path('uploads/apparel/products');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $nextSort = (int) $product->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $image) {
            if (!$image || !$image->isValid()) {
                continue;
            }

            $name = time() . '_' . $image->getClientOriginalName();
            $image->move($path, $name);

            $product->images()->create([
                'image_path' => 'uploads/apparel/products/' . $name,
                'alt_text' => $product->name,
                'sort_order' => $nextSort++,
            ]);
        }
    }

    private function deleteRemovedImages(Request $request, ApparelProduct $product): void
    {
        $imageIds = $request->input('remove_images', []);
        $variantImageIds = $request->input('remove_variant_images', []);

        if (!empty($imageIds)) {
            ApparelProductImage::where('apparel_product_id', $product->id)
                ->whereNull('apparel_product_variant_id')
                ->whereIn('id', $imageIds)
                ->delete();
        }

        if (!empty($variantImageIds)) {
            ApparelProductImage::where('apparel_product_id', $product->id)
                ->whereNotNull('apparel_product_variant_id')
                ->whereIn('id', $variantImageIds)
                ->delete();
        }
    }

    private function storeVariantImages(Request $request, ApparelProduct $product, $variant, int $index): void
    {
        if (!$request->hasFile("variants.{$index}.images")) {
            return;
        }

        $path = public_path('uploads/apparel/products/variants');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $nextSort = (int) $variant->images()->max('sort_order') + 1;

        foreach ($request->file("variants.{$index}.images", []) as $image) {
            if (!$image || !$image->isValid()) {
                continue;
            }

            $name = time() . '_' . $variant->id . '_' . $image->getClientOriginalName();
            $image->move($path, $name);

            $variant->images()->create([
                'apparel_product_id' => $product->id,
                'image_path' => 'uploads/apparel/products/variants/' . $name,
                'alt_text' => trim($product->name . ' ' . ($variant->option_value ?: $variant->color_name ?: $variant->size)),
                'sort_order' => $nextSort++,
            ]);
        }
    }
}
