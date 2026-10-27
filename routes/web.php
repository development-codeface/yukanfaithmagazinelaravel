<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Admin Controllers
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\MagazineIssueController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ApparelCategoryController;
use App\Http\Controllers\Admin\ApparelProductController;
use App\Http\Controllers\Auth\LoginController;

Route::redirect('/', '/home');

Route::get('/admin/login', [LoginController::class, 'showAdminLoginForm'])
    ->middleware('guest')
    ->name('admin.login');


// Admin Routes
Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['auth', 'auth.gates']  // Add 'web' and your custom middleware alias here
], function () {
    Route::redirect('/', '/admin/report')->name('home');

    // Permissions
    Route::delete('permissions/destroy', [PermissionsController::class, 'massDestroy'])->name('permissions.massDestroy');
    Route::resource('permissions', PermissionsController::class);

    //Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

        //Category
    Route::resource('categories', CategoryController::class);
     Route::post(
        'articles/upload-image',
        [ArticleController::class, 'uploadImage']
    )->name('article.image.upload');

    Route::resource('articles', ArticleController::class);
    Route::delete('gallery/{id}', [ArticleController::class, 'deleteGallery'])
    ->name('gallery.delete');

    Route::resource(  'magazine-issues', MagazineIssueController::class);
    Route::resource('ad-banners', \App\Http\Controllers\Admin\AdBannerController::class)->except('show');
   
    // Roles
    Route::delete('roles/destroy', [RolesController::class, 'massDestroy'])->name('roles.massDestroy');
    Route::resource('roles', RolesController::class);

    // Users
    Route::delete('users/destroy', [UsersController::class, 'massDestroy'])->name('users.massDestroy');
    Route::resource('users', UsersController::class);
    // Block a user
    Route::put('users/{user}/block', [UsersController::class, 'block'])->name('users.block');

    // Unblock a user
    Route::put('users/{user}/unblock', [UsersController::class, 'unblock'])->name('users.unblock');

     Route::resource('plans', PlanController::class);
     Route::get('plans/status/{id}', 
        [\App\Http\Controllers\Admin\PlanController::class, 'changeStatus']
    )->name('plans.status');

    Route::get('subscriptions',
    [\App\Http\Controllers\Admin\SubscriptionController::class, 'index']
    )->name('subscriptions.index');
    Route::patch('subscriptions/{subscription}/acknowledge-print', [\App\Http\Controllers\Admin\SubscriptionController::class, 'acknowledgePrint'])->name('subscriptions.acknowledge-print');
    Route::get('magazine-purchases', [\App\Http\Controllers\Admin\MagazinePurchaseController::class, 'index'])->name('magazine-purchases.index');
    Route::patch('magazine-purchases/{purchase}/acknowledge', [\App\Http\Controllers\Admin\MagazinePurchaseController::class, 'acknowledge'])->name('magazine-purchases.acknowledge');

    Route::resource('events', EventController::class);

    // Apparel Store
    Route::get('apparel-orders', [\App\Http\Controllers\Admin\ApparelOrderController::class, 'index'])->name('apparel-orders.index');
    Route::get('apparel-orders/{order}', [\App\Http\Controllers\Admin\ApparelOrderController::class, 'show'])->name('apparel-orders.show');
    Route::patch('apparel-orders/{order}', [\App\Http\Controllers\Admin\ApparelOrderController::class, 'update'])->name('apparel-orders.update');
    Route::resource('apparel-categories', ApparelCategoryController::class)->except(['show']);
    Route::resource('apparel-products', ApparelProductController::class)->except(['show']);

    // Banners
    Route::get('banners', [BannerController::class, 'index'])->name('banners.index');
    Route::get('banners/create', [BannerController::class, 'create'])->name('banners.create');
    Route::post('banners/store', [BannerController::class, 'store'])->name('banners.store');
    Route::post('banners/status-change/{id}', [BannerController::class, 'statusChange'])->name('banners.status.change');
    Route::get('banners/edit/{id}', [BannerController::class, 'edit'])->name('banners.edit');
    Route::post('banners/update/{id}', [BannerController::class, 'update'])->name('banners.update');
    Route::delete('banners/destroy/{id}', [BannerController::class, 'destroy'])->name('banners.destroy');

});

// Profile / Change Password Routes
Route::group([
    'prefix' => 'profile',
    'as' => 'profile.',
    'middleware' => ['web', 'auth', 'auth.gates']  // Same here for profile routes
], function () {
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', [ChangePasswordController::class, 'edit'])->name('password.edit');
        Route::post('password', [ChangePasswordController::class, 'update'])->name('password.update');
    }
});

Auth::routes();

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('dashboard');
// Home
Route::get('/', [FrontendController::class, 'home'])->name('home');

Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/subscriptions', [UserDashboardController::class, 'subscriptions'])->name('subscriptions');
    Route::post('/subscribe/{plan}', [UserDashboardController::class, 'subscribe'])->name('subscribe');
    Route::get('/subscribe/stripe/success', [UserDashboardController::class, 'stripeSuccess'])->name('subscribe.success');
    Route::get('/subscribe/stripe/cancel', [UserDashboardController::class, 'stripeCancel'])->name('subscribe.cancel');
    Route::post('/article/{article}/purchase', [UserDashboardController::class, 'purchaseArticle'])->name('article.purchase');
    Route::get('/article/{article}', [UserDashboardController::class, 'article'])->name('article');
    Route::get('/magazine/{magazineIssue}/reader', [UserDashboardController::class, 'magazineReader'])->name('magazine.reader');
});

// Category by slug or ID
Route::prefix('apparel')->name('apparel.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ApparelStoreController::class, 'index'])->name('index');
    Route::get('/category/{category}', [\App\Http\Controllers\ApparelStoreController::class, 'index'])->name('category');
    Route::get('/product/{product}', [\App\Http\Controllers\ApparelStoreController::class, 'show'])->name('product');
    Route::post('/product/{product}/cart', [\App\Http\Controllers\ApparelStoreController::class, 'addToCart'])->name('cart.add');
    Route::get('/cart', [\App\Http\Controllers\ApparelStoreController::class, 'cart'])->name('cart');
    Route::patch('/cart/{key}', [\App\Http\Controllers\ApparelStoreController::class, 'updateCart'])->name('cart.update');
    Route::get('/checkout', [\App\Http\Controllers\ApparelOrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/account', [\App\Http\Controllers\ApparelOrderController::class, 'account'])->middleware('throttle:6,1')->name('checkout.account');
    Route::middleware('auth')->group(function () {
        Route::post('/checkout', [\App\Http\Controllers\ApparelOrderController::class, 'store'])->name('checkout.store');
        Route::get('/orders', [\App\Http\Controllers\ApparelOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\ApparelOrderController::class, 'show'])->name('orders.show');
    });
    Route::delete('/cart/{key}', [\App\Http\Controllers\ApparelStoreController::class, 'removeFromCart'])->name('cart.remove');
});

Route::get('/category/{category}', [FrontendController::class, 'category'])
    ->name('frontend.category');

// Article by slug or ID
Route::get('/article/{article}', [FrontendController::class, 'article'])
    ->name('frontend.article');

// Event by ID
Route::get('/event/{id}', [FrontendController::class, 'event'])
    ->name('frontend.event');

Route::get('/magazine/{magazineIssue}', [\App\Http\Controllers\MagazineController::class, 'show'])->name('frontend.magazine');
Route::middleware('auth')->group(function () {
    Route::get('/user/magazines/payment/success', [\App\Http\Controllers\MagazineController::class, 'success'])->name('user.magazine.success');
    Route::get('/user/magazine/{magazineIssue}/buy', [\App\Http\Controllers\MagazineController::class, 'buy'])->name('user.magazine.buy');
    Route::post('/user/magazine/{magazineIssue}/checkout', [\App\Http\Controllers\MagazineController::class, 'checkout'])->name('user.magazine.checkout');
});

Route::get('/user/article/{article}/buy-magazine', [\App\Http\Controllers\MagazineController::class, 'buyArticle'])->name('user.article.magazine.buy');

Route::post('/stripe/magazines/webhook', [\App\Http\Controllers\MagazineController::class, 'webhook'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->name('magazine.webhook');
