<?php

namespace Tests\Feature;

use App\Models\AdBanner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdBannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_26_000001_create_ad_banners_table.php'))->up();
    }

    protected function tearDown(): void
    {
        foreach (AdBanner::all() as $ad) {
            if (str_starts_with($ad->image, 'uploads/ads/') && is_file(public_path($ad->image))) {
                unlink(public_path($ad->image));
            }
        }
        parent::tearDown();
    }

    public function test_public_api_and_home_partial_include_only_active_ads(): void
    {
        $active = AdBanner::create(['title' => null, 'image' => 'images/ad.jpg', 'link' => 'https://example.com/offer', 'status' => true]);
        AdBanner::create(['title' => 'Hidden ad', 'image' => 'images/hidden.jpg', 'link' => 'https://example.com/hidden', 'status' => false]);
        $this->getJson('/api/ad-banners')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)->assertJsonPath('data.0.title', null)
            ->assertJsonPath('data.0.status', true)->assertJsonPath('data.0.image_url', asset('images/ad.jpg'));
        $html = view('partials.ad-banners', ['adBanners' => AdBanner::active()->get()])->render();
        $this->assertStringContainsString('https://example.com/offer', $html);
        $this->assertStringNotContainsString('https://example.com/hidden', $html);
        $active->update(['status' => false]);
        $this->getJson('/api/ad-banners')->assertJsonCount(0, 'data');
    }

    public function test_admin_upload_and_edit_preserve_image_and_allow_inactive_status(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\AuthGates::class);
        Gate::define('article_access', fn () => true);
        $this->actingAs((new User)->forceFill(['id' => 1, 'name' => 'Admin']));
        $this->post(route('admin.ad-banners.store'), [
            'image' => UploadedFile::fake()->image('offer.png'),
            'link' => 'https://example.com/offer', 'status' => 1,
        ])->assertRedirect(route('admin.ad-banners.index'));
        $ad = AdBanner::firstOrFail();
        $this->assertFileExists(public_path($ad->image));
        $image = $ad->image;
        $this->put(route('admin.ad-banners.update', $ad), [
            'title' => 'Updated offer', 'link' => 'https://example.com/new-offer', 'status' => 0,
        ])->assertRedirect(route('admin.ad-banners.index'));
        $this->assertFalse($ad->fresh()->status);
        $this->assertSame($image, $ad->fresh()->image);
        $this->post(route('admin.ad-banners.store'), [
            'image' => UploadedFile::fake()->image('invalid.png'), 'link' => 'javascript:alert(1)', 'status' => 1,
        ])->assertSessionHasErrors('link');
        $this->assertSame(1, AdBanner::count());
    }

    public function test_guests_and_users_without_permission_cannot_manage_ads(): void
    {
        $this->get(route('admin.ad-banners.create'))->assertRedirect(route('login'));
        $this->withoutMiddleware(\App\Http\Middleware\AuthGates::class);
        Gate::define('article_access', fn () => false);
        $this->actingAs((new User)->forceFill(['id' => 1, 'name' => 'Reader']))
            ->get(route('admin.ad-banners.create'))->assertForbidden();
    }
}
