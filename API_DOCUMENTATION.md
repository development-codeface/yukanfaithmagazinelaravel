# Mobile API Documentation

## Base URL
```
https://yourdomain.com/api
```

## Available Endpoints

### 1. Banners API

#### Get Active Banners (Paginated)
- **GET** `/api/banners`
- **Query Parameters:**
  - `per_page` (optional): Number of results per page (default: 10)
  - `page` (optional): Page number (default: 1)
- **Response:** Paginated list of active banners

#### Get All Active Banners (Without Pagination)
- **GET** `/api/banners/active`
- **Response:** List of all active banners

#### Get Single Banner
- **GET** `/api/banners/{id}`
- **Response:** Single banner details

---

### 2. Categories API

#### List All Categories (Paginated)
- **GET** `/api/categories`
- **Query Parameters:**
  - `search` (optional): Search by category name or description
  - `per_page` (optional): Number of results per page (default: 50)
- **Response:** Paginated list of categories

#### Get All Categories (Simple List)
- **GET** `/api/categories/all`
- **Response:** List of all categories (no pagination, for dropdowns)

#### Get Single Category
- **GET** `/api/categories/{id}`
- **Response:** Single category details

---

### 3. Events API

#### List Events (Paginated)
- **GET** `/api/events`
- **Query Parameters:**
  - `search` (optional): Search by title or description
  - `sort_by` (optional): Sort field (default: created_at)
  - `sort_order` (optional): asc or desc (default: desc)
  - `per_page` (optional): Number of results per page (default: 20)
  - `page` (optional): Page number
- **Response:** Paginated list of active events

#### Search Events
- **GET** `/api/events/search`
- **Query Parameters:**
  - `q` (required): Search query (min 2 chars)
- **Response:** Paginated search results

#### Get Upcoming Events
- **GET** `/api/events/upcoming`
- **Response:** List of 8 latest active events

#### Get Event Statistics
- **GET** `/api/events/stats`
- **Response:** Event count statistics and latest event

#### Get Single Event
- **GET** `/api/events/{id}`
- **Response:** Single event details including title, subtitle, description, image, button_link, status, dates

---

### 4. Articles API

#### List Published Articles (Paginated)
- **GET** `/api/articles`
- **Query Parameters:**
  - `category_id` (optional): Filter by category ID
  - `search` (optional): Search by title, summary, or content
  - `sort_by` (optional): Sort field (default: published_at)
  - `sort_order` (optional): asc or desc (default: desc)
  - `per_page` (optional): Number of results per page (default: 12)
  - `page` (optional): Page number
- **Response:** Paginated list of published articles

#### Get Featured Articles
- **GET** `/api/articles/featured`
- **Response:** 6 latest published articles

#### Search Articles
- **GET** `/api/articles/search`
- **Query Parameters:**
  - `q` (required): Search query (min 2 chars)
- **Response:** Paginated search results

#### Get Articles by Category
- **GET** `/api/articles/category/{categoryId}`
- **Response:** Paginated articles from specific category

#### Get Article Statistics
- **GET** `/api/articles/stats`
- **Response:** Article count statistics and latest article

#### Get Single Article
- **GET** `/api/articles/{id}`
- **Response:** Complete article details including content, blocks, gallery images, author, category

#### Read Paid Article as Authenticated User
- **GET** `/api/user/articles/{id}`
- **Headers:** `Authorization: Bearer {token}`
- **Access:** Allowed when the article is free, the user has an active subscription, or the user purchased this article.

#### Buy Single Article
- **POST** `/api/user/articles/{id}/purchase`
- **Headers:** `Authorization: Bearer {token}`
- **Body Parameters:**
  - `transaction_id` (optional): Payment gateway transaction/reference ID
  - `amount_paid` (optional): Amount confirmed by payment gateway. Defaults to the article single price.
- **Response:** Purchase record and unlocked article details

Example response:
```json
{
  "message": "Article purchased successfully.",
  "purchase": {
    "id": 1,
    "user_id": 5,
    "article_id": 12,
    "amount_paid": "19.00",
    "payment_status": true,
    "transaction_id": "PAYMENT-123",
    "purchased_at": "2026-05-14T10:00:00Z"
  },
  "article": {
    "id": 12,
    "title": "Article Title",
    "access_type": "paid",
    "single_article_price": "19.00",
    "can_read": true,
    "is_locked": false
  }
}
```

---

### 5. Magazine Issues API

#### List Magazine Issues (Paginated)
- **GET** `/api/magazines`
- **Query Parameters:**
  - `search` (optional): Search by title or description
  - `sort_by` (optional): Sort field (default: published_at)
  - `sort_order` (optional): asc or desc (default: desc)
  - `per_page` (optional): Number of results per page (default: 12)
  - `page` (optional): Page number
- **Response:** Paginated list of magazine issues

#### Get Latest Magazine Issues
- **GET** `/api/magazines/latest`
- **Response:** 8 latest magazine issues

#### Search Magazine Issues
- **GET** `/api/magazines/search`
- **Query Parameters:**
  - `q` (required): Search query (min 2 chars)
- **Response:** Paginated search results

#### Get Magazine Statistics
- **GET** `/api/magazines/stats`
- **Response:** Magazine count statistics

#### Get Single Magazine Issue (with Articles)
- **GET** `/api/magazines/{id}`
- **Response:** Magazine details including cover image, PDF URL, and all associated articles

---

### 6. Subscription Plans API

#### List Active Subscription Plans
- **GET** `/api/plans`
- **Query Parameters:**
  - `search` (optional): Search by plan name or description
- **Response:** List of active subscription plans

Example response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Monthly Plan",
      "description": "Read all paid articles and magazine issues.",
      "price": "99.00",
      "duration": {
        "value": 1,
        "type": "months",
        "days": 30,
        "label": "1 month"
      },
      "features": [],
      "is_active": true,
      "created_at": "2026-05-06T10:00:00Z",
      "updated_at": "2026-05-06T10:00:00Z"
    }
  ]
}
```

#### Get Single Subscription Plan
- **GET** `/api/plans/{id}`
- **Response:** Single active subscription plan details

#### Subscribe Authenticated User to a Plan
- **POST** `/api/user/subscribe/{plan}`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Created subscription with selected plan

---

## Response Format

### Success Response
```json
{
  "data": [
    {
      "id": 1,
      "title": "Event Title",
      "subtitle": "Subtitle",
      "description": "Description",
      "image": "https://domain.com/image.jpg",
      "button_link": "https://example.com",
      "status": 1,
      "created_at": "2026-05-06T10:00:00Z",
      "updated_at": "2026-05-06T10:00:00Z"
    }
  ],
  "links": {
    "first": "https://domain.com/api/events?page=1",
    "last": "https://domain.com/api/events?page=3",
    "prev": null,
    "next": "https://domain.com/api/events?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "https://domain.com/api/events",
    "per_page": 20,
    "to": 20,
    "total": 50
  }
}
```

### Error Response
```json
{
  "message": "Not found",
  "status": 404
}
```

---

## Common Query Parameters

| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `page` | integer | `?page=1` | Page number for pagination |
| `per_page` | integer | `?per_page=20` | Number of items per page |
| `search` | string | `?search=query` | Search text |
| `q` | string | `?q=query` | Alternative search parameter |
| `sort_by` | string | `?sort_by=created_at` | Field to sort by |
| `sort_order` | string | `?sort_order=desc` | Sort order (asc/desc) |
| `category_id` | integer | `?category_id=5` | Filter by category |

---

## Status Codes

- **200 OK** - Successful request
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation error
- **500 Internal Server Error** - Server error

---

## Example Usage

### Get Upcoming Events
```
GET /api/events/upcoming
```

### Search for Articles
```
GET /api/articles/search?q=magazine&per_page=15
```

### Get Magazine with Articles
```
GET /api/magazines/1
```

### Get Subscription Plans
```
GET /api/plans
```

### List Events with Sorting
```
GET /api/events?sort_by=title&sort_order=asc&per_page=10
```
