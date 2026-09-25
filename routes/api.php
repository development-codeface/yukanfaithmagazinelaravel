<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\MagazineIssueController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PlanController;

Route::middleware('api')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['message' => 'API is working!']);
    });

    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');

    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('api.plans.index');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('api.plans.show');
    });

    Route::middleware('api.token')->prefix('user')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('/dashboard', [UserController::class, 'dashboard'])->name('api.user.dashboard');
        Route::get('/subscription/status', [UserController::class, 'subscriptionStatus'])->name('api.user.subscription.status');
        Route::post('/subscribe/{plan}', [UserController::class, 'subscribe'])->name('api.user.subscribe');
        Route::post('/subscription/payment-intent/{plan}', [UserController::class, 'createSubscriptionPaymentIntent'])->name('api.user.subscription.payment-intent');
        Route::post('/subscription/confirm-payment', [UserController::class, 'confirmSubscriptionPayment'])->name('api.user.subscription.confirm-payment');
        Route::post('/articles/{article}/purchase', [UserController::class, 'purchaseArticle'])->name('api.user.articles.purchase');
        Route::get('/articles/{article}', [UserController::class, 'article'])->name('api.user.articles.show');
        Route::get('/magazines/{magazine_issue}/reader', [UserController::class, 'magazineReader'])->name('api.user.magazines.reader');
    });

    // Banner API Routes
    Route::prefix('banners')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('api.banners.index');
        Route::get('/active', [BannerController::class, 'active'])->name('api.banners.active');
        Route::get('/{banner}', [BannerController::class, 'show'])->name('api.banners.show');
    });

    // Category API Routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('api.categories.index');
        Route::get('/all', [CategoryController::class, 'all'])->name('api.categories.all');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('api.categories.show');
        });

    // Event API Routes - Public endpoints
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('api.events.index');
        Route::get('/search', [EventController::class, 'search'])->name('api.events.search');
        Route::get('/upcoming', [EventController::class, 'upcoming'])->name('api.events.upcoming');
        Route::get('/stats', [EventController::class, 'stats'])->name('api.events.stats');
        Route::get('/{event}', [EventController::class, 'show'])->name('api.events.show');
    });

    // Article API Routes
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index'])->name('api.articles.index');
        Route::get('/latest', [ArticleController::class, 'latest'])->name('api.articles.latest');
        Route::get('/featured', [ArticleController::class, 'featured'])->name('api.articles.featured');
        Route::get('/free', [ArticleController::class, 'free'])->name('api.articles.free');
        Route::get('/paid', [ArticleController::class, 'paid'])->name('api.articles.paid');
        Route::get('/search', [ArticleController::class, 'search'])->name('api.articles.search');
        Route::get('/category/{categoryId}', [ArticleController::class, 'byCategory'])->name('api.articles.byCategory');
        Route::get('/pastMagazines', [ArticleController::class, 'past_magazines'])->name('api.articles.pastMagazines');
        Route::get('/stats', [ArticleController::class, 'stats'])->name('api.articles.stats');
        Route::get('/{article}', [ArticleController::class, 'show'])->name('api.articles.show');
    });

    // Magazine Issues API Routes
    Route::prefix('magazines')->group(function () {
        Route::get('/', [MagazineIssueController::class, 'index'])->name('api.magazines.index');
        Route::get('/latest', [MagazineIssueController::class, 'latest'])->name('api.magazines.latest');
        Route::get('/search', [MagazineIssueController::class, 'search'])->name('api.magazines.search');
        Route::get('/stats', [MagazineIssueController::class, 'stats'])->name('api.magazines.stats');
        Route::get('/{magazine_issue}', [MagazineIssueController::class, 'show'])->name('api.magazines.show');
    });
});
