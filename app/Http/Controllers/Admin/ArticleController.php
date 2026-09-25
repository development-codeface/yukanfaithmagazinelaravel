<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleBlock;
use App\Models\ArticleGalleryImage;
use App\Models\Category;
use App\Models\User;
use App\Models\MagazineIssue;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArticleController extends Controller
{
    public function index()
    {
        $query = Article::with(['category', 'categories', 'author']);

        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if (request()->filled('category_id')) {
            $categoryId = request('category_id');
            $query->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereHas('categories', function ($query) use ($categoryId) {
                        $query->where('categories.id', $categoryId);
                    });
            });
        }

        if (request()->filled('author_id')) {
            $query->where('author_id', request('author_id'));
        }

        if (request()->filled('access_type')) {
            $query->where('access_type', request('access_type'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        $articles = $query->latest()->paginate(15)->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'categories' => Category::pluck('category_name', 'id'),
            'authors' => User::pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.articles.create', [
            'categories' => Category::pluck('category_name', 'id'),
            'authors'    => User::pluck('name', 'id'),
            'issues'     => MagazineIssue::pluck('title', 'id'),
        ]);
    }



public function store(Request $request)
{
    $data = $request->validate([
        'title'        => 'required|string|max:255',
       // 'category_id'  => 'required|integer',
        'author_id'    => 'required|integer',
        'summary'      => 'nullable|string',
        'access_type'  => 'required|in:free,paid',
        'single_article_price' => 'nullable|numeric|min:0',
        'status'       => 'required|in:draft,published',
        'left_side_fixed_image' => 'nullable|image',
        'article_featured_image' => 'nullable|image',
        'buy_button_link'=>'nullable|string',
        'left_image_title'=>'nullable|string',
        'categories'   => 'required|array|min:1',
        'categories.*' => 'integer|exists:categories,id',
    ]);

    $data['category_id'] = $request->categories[0];
    $data['content'] = $request->input('content', '');

    if ($request->hasFile('left_side_fixed_image')) {
        $img = $request->file('left_side_fixed_image');
        $name = time().'_'.$img->getClientOriginalName();
        $destinationPath = public_path('uploads/articles/covers');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $img->move($destinationPath, $name);
        $data['featured_image_url'] = 'uploads/articles/covers/'.$name;
    }

    if ($request->hasFile('article_featured_image')) {
        $img = $request->file('article_featured_image');
        $name = time().'_'.$img->getClientOriginalName();
        $destinationPath = public_path('uploads/articles/featured');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $img->move($destinationPath, $name);
        $data['article_featured_image_url'] = 'uploads/articles/featured/'.$name;
    }

    if ($data['status'] === 'published') {
        $data['published_at'] = now();
    }

    $article = Article::create($data);
    $article->categories()->sync($request->categories ?? []);


    // Save Blocks
    foreach ($request->input('blocks', []) as $order => $block) {

        if (!isset($block['data'])) continue;

        $blockData = $block['data'];

        // Upload image inside block
        if ($request->hasFile("blocks.$order.data.image")) {

            $img = $request->file("blocks.$order.data.image");
            $name = time().'_'.$img->getClientOriginalName();
            $img->move(public_path('uploads/articles/blocks'), $name);

            $blockData['image'] = 'uploads/articles/blocks/'.$name;
        }

        ArticleBlock::create([
            'article_id' => $article->id,
            'block_type' => $block['type'] ?? 'text_image',
            'block_order'=> (int) $order,  // ✅ INTEGER SAFE
            'block_data' => $blockData,
        ]);
    }

    if ($request->hasFile('gallery_images')) {

    foreach ($request->file('gallery_images') as $image) {

        if ($image) {

            $name = time().'_'.$image->getClientOriginalName();
            $image->move(public_path('uploads/articles/gallery'), $name);

            \App\Models\ArticleGalleryImage::create([
                'article_id' => $article->id,
                'image_path' => 'uploads/articles/gallery/'.$name
            ]);
        }
    }
}

    return redirect()
        ->route('admin.articles.index')
        ->with('success', 'Article created successfully');
}


    public function edit(Article $article)
    {
        return view('admin.articles.edit', [
            'article'    => $article,
            'categories' => Category::pluck('category_name', 'id'),
            'authors'    => User::pluck('name', 'id'),
            'issues'     => MagazineIssue::pluck('title', 'id'),
        ]);
    }

   public function update(Request $request, Article $article)
{
    $data = $request->validate([
        'title'        => 'required|string|max:255',
       // 'category_id'  => 'required|integer',
        'author_id'    => 'required|integer',
        'summary'      => 'nullable|string',
        'access_type'  => 'required|in:free,paid',
        'single_article_price' => 'nullable|numeric|min:0',
        'status'       => 'required|in:draft,published',
        'left_side_fixed_image' => 'nullable|image',
        'article_featured_image' => 'nullable|image',
         'buy_button_link'=>'nullable|string',
        'left_image_title'=>'nullable|string',
        'categories'   => 'required|array|min:1',
        'categories.*' => 'integer|exists:categories,id',
    ]);

    $data['category_id'] = $request->categories[0];

    if ($request->boolean('remove_left_side_fixed_image')) {
        if ($article->featured_image_url && file_exists(public_path($article->featured_image_url))) {
            unlink(public_path($article->featured_image_url));
        }
        $data['featured_image_url'] = null;
    } elseif ($request->hasFile('left_side_fixed_image')) {
        if ($article->featured_image_url && file_exists(public_path($article->featured_image_url))) {
            unlink(public_path($article->featured_image_url));
        }
        $img = $request->file('left_side_fixed_image');
        $name = time().'_'.$img->getClientOriginalName();
        $destinationPath = public_path('uploads/articles/covers');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $img->move($destinationPath, $name);
        $data['featured_image_url'] = 'uploads/articles/covers/'.$name;
    }

    if ($request->boolean('remove_article_featured_image')) {
        if ($article->article_featured_image_url && file_exists(public_path($article->article_featured_image_url))) {
            unlink(public_path($article->article_featured_image_url));
        }
        $data['article_featured_image_url'] = null;
    } elseif ($request->hasFile('article_featured_image')) {
        if ($article->article_featured_image_url && file_exists(public_path($article->article_featured_image_url))) {
            unlink(public_path($article->article_featured_image_url));
        }
        $img = $request->file('article_featured_image');
        $name = time().'_'.$img->getClientOriginalName();
        $destinationPath = public_path('uploads/articles/featured');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $img->move($destinationPath, $name);
        $data['article_featured_image_url'] = 'uploads/articles/featured/'.$name;
    }

    $article->update($data);

    $article->categories()->sync($request->categories ?? []);


    // Remove old blocks
    $article->blocks()->delete();

    foreach ($request->input('blocks', []) as $order => $block) {

        if (!isset($block['data'])) continue;

        $blockData = $block['data'];

       // Remove image checked
        if (!empty($blockData['remove_image'])) {

            if (!empty($blockData['existing_image']) && file_exists(public_path($blockData['existing_image']))) {
                unlink(public_path($blockData['existing_image']));
            }
            $blockData['image'] = null;

        } elseif ($request->hasFile("blocks.$order.data.image")) {

            if (!empty($blockData['existing_image']) && file_exists(public_path($blockData['existing_image']))) {
                unlink(public_path($blockData['existing_image']));
            }
            $img = $request->file("blocks.$order.data.image");
            $name = time().'_'.$img->getClientOriginalName();
            $img->move(public_path('uploads/articles/blocks'), $name);
            $blockData['image'] = 'uploads/articles/blocks/'.$name;

        } elseif (isset($blockData['existing_image'])) {

            $blockData['image'] = $blockData['existing_image'];
        }


        ArticleBlock::create([
            'article_id' => $article->id,
            'block_type' => $block['type'] ?? 'text_image',
            'block_order'=> (int) $order,
            'block_data' => $blockData,
        ]);
    }


    // Save new gallery images (only new ones)
if ($request->hasFile('gallery_images')) {

    foreach ($request->file('gallery_images') as $image) {

        if ($image) {

            $name = time().'_'.$image->getClientOriginalName();
            $image->move(public_path('uploads/articles/gallery'), $name);

            \App\Models\ArticleGalleryImage::create([
                'article_id' => $article->id,
                'image_path' => 'uploads/articles/gallery/'.$name
            ]);
        }
    }
}


    return redirect()
        ->route('admin.articles.index')
        ->with('success', 'Article updated successfully');
}

    public function destroy(Article $article)
    {
        $article->delete();
        return back()->with('success', 'Article deleted');
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {

            $file = $request->file('upload');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/articles'), $filename);

            return response()->json([
                'uploaded' => 1,
                'fileName' => $filename,
                'url' => asset('uploads/articles/'.$filename),
            ]);
        }
    }
}
