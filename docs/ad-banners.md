Ads Banners

Admin sidebar includes Magazine Issues and Ads Banners for users with article_access permission.

Use Admin > Ads Banners to add, edit or delete advertisements. Each ad requires an image (JPG, PNG or WebP, maximum 4 MB) and an HTTP/HTTPS link. Title is optional. Status can be Active or Inactive. Editing without uploading another image retains the existing image.

Active ads appear immediately after Latest Magazine Features and before Past Magazines on the homepage, newest first. Images fit the available width and retain their proportions. Clicking opens the configured link in a new tab. Existing hero banners are managed separately.

Public API: GET /api/ad-banners

No authentication required. Returns only active advertisements, newest first, in the same order as the homepage. An empty collection returns {"data":[]}.

Example response:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Summer offer",
      "image_url": "https://your-domain.com/uploads/ads/example.jpg",
      "link": "https://advertiser.example.com/offer",
      "status": true
    }
  ]
}
```

The title may be null. Mobile clients should use image_url to display the banner and link as its tap destination.

Deployment: php artisan migrate
