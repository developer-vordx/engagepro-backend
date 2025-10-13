# 📦 Meedyo Backend

> **Meedyo** is an AI-powered social media content creation and management platform that helps creators, marketers, and businesses generate, design, schedule, and optimize engaging content across **21 social media platforms** —all in one intuitive dashboard.

---

## 🚀 Features

- ✍️ **AI Content Generator** – Automatically craft captions, hashtags, and post ideas tailored to your audience and brand tone
- 🎨 **Visual Post Designer** – Create scroll-stopping visuals using easy drag-and-drop templates and royalty-free images
- 📅 **Smart Scheduler** – Plan and auto-schedule content to post at the optimal times for maximum engagement
- 📊 **Performance Insights** – Analyze engagement metrics and get actionable recommendations to improve your strategy
- 🧠 **Brand Voice Training** – Train the AI with your previous content so it can mimic your unique voice
- 👥 **Team Collaboration** – Work with your team in real-time on campaigns and content calendars
- 🔗 **21 Platform Integration** – Connect and post to TikTok, X, Facebook, Instagram, YouTube, LinkedIn, and 15 more platforms

---

## 🌐 Integrated Social Media Platforms

### ✅ **Fully Supported (17 Platforms)**
| Platform | OAuth Type | Key Features | Status |
|----------|-----------|--------------|--------|
| **TikTok** | OAuth 2.0 + PKCE | Video upload, 287MB max, 10 scopes | ✅ Active |
| **X (Twitter)** | Dual OAuth (1.0a + 2.0) | Text/image/video, 18 scopes | ✅ Active |
| **Facebook** | OAuth 2.0 | Page posting, 12 scopes | ✅ Active |
| **Instagram** | OAuth 2.0 | Business posting, 8 scopes | ✅ Active |
| **YouTube** | OAuth 2.0 | Video upload, 128GB max, 7 scopes | ✅ Active |
| **LinkedIn** | OAuth 2.0 | Professional content, w_member_social | ✅ Active |
| **Reddit** | OAuth 2.0 | Community posting, submit scope | ✅ Active |
| **Pinterest** | OAuth 2.0 | Pin creation, images only | ✅ Active |
| **Vimeo** | OAuth 2.0 | Video upload, TUS protocol | ✅ Active |
| **Dailymotion** | OAuth 2.0 | Video upload, 4GB max | ✅ Active |
| **Tumblr** | OAuth 1.0a | Blogging, NPF format | ✅ Active |
| **Mastodon** | OAuth 2.0 | Federated posting, instance-based | ✅ Active |
| **Twitch** | OAuth 2.0 | Stream management, Helix API | ✅ Active |
| **Telegram** | Bot API | Messaging, bot token auth | ✅ Active |
| **Threads** | OAuth 2.0 | Meta infrastructure, container method | ✅ Active |
| **Bluesky** | AT Protocol | App passwords, 1MB images | ✅ Active |
| **Truth Social** | OAuth 2.0 | Mastodon-compatible API | ✅ Active |

### ⚠️ **Limited Support (2 Platforms)**
| Platform | Auth Type | Limitation | Status |
|----------|-----------|------------|--------|
| **Snapchat** | OAuth 2.0 | Auth only (no posting) | ⚠️ Limited |
| **Odysee** | API Key | Requires LBRY SDK | ⚠️ Limited |

### 🔄 **Cookie-Based (1 Platform)**
| Platform | Auth Type | Note | Status |
|----------|-----------|------|--------|
| **Minds** | Cookie-based | Not suitable for OAuth | ⚠️ Limited |

---

## 🚀 Tech Stack

- **Framework:** Laravel 11
- **Language:** PHP 8.2+
- **Architecture:** RESTful API with Service Layer Pattern
- **Testing:** PHPUnit, Laravel Test Factory
- **Authentication:** JWT (JSON Web Tokens) + OAuth 2.0/1.0a
- **Database:** MySQL / PostgreSQL (configurable)
- **Queue System:** Laravel Queues (Redis, SQS, or Database)
- **API Documentation:** OpenAPI 3.0 + Swagger
- **Service Providers:** Custom Dependency Injection
- **Social Media APIs:** 21 platforms integrated

---

## 📁 Project Structure

```
    app/
    ├── Http/
    │   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── CustomerBackOffice/
│   │       │   ├── Auth/
│   │       │   ├── Post/
│   │       │   └── Social/
│   ├── Middleware/
│   │   ├── JwtCustomerAuthMiddleware.php
│   │   ├── SubscriptionValidationMiddleware.php
│   │   └── SocialMediaSecurityMiddleware.php
│   └── Requests/
│       └── Api/V1/CustomerBackOffice/
├── Library/
│   └── SocialManager/
│       ├── TikTokService.php
│       ├── XService.php
│       ├── MetaService.php
│       └── ... (21 platform services)
    ├── Services/
│   └── Api/V1/CustomerBackOffice/
│       ├── Post/
│       │   ├── CreatePostService.php
│       │   ├── UploadPostService.php
│       │   └── PublishPostService.php
│       └── Social/
│           ├── GetAuthUrlService.php
│           ├── HandleCallbackService.php
│           └── DisconnectAccountService.php
├── Models/
│   ├── Customer.php
│   ├── Post.php
│   ├── PostFile.php
│   ├── SocialAccount.php
│   └── CustomerAccount.php
└── Helper.php (Common utilities)

database/
├── migrations/
└── seeders/
    ├── SocialAccountSeeder.php (21 platforms)
    └── SubscriptionPlanSeeder.php

config/
└── services.php (21 platform credentials)
```

---

## 🛠️ Installation & Setup

```bash
# Clone the repository
git clone https://github.com/developer-vordx/engagepro-backend.git
cd engagepro-backend

# Install dependencies
composer install

# Copy .env and configure
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=engagepro
DB_USERNAME=root
DB_PASSWORD=

# Setup database
php artisan migrate 
php artisan db:seed

# Generate JWT secret
php artisan jwt:secret

# Run tests
php artisan test

# Start local server
php artisan serve

# Start queue worker (for email notifications)
php artisan queue:work
```

---

## 🔑 Social Media Platform Setup

### Required Environment Variables

Add these to your `.env` file for each platform you want to use:

```env
# TikTok
TIKTOK_CLIENT_ID=your_client_id
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URI=http://localhost:8000/api/v1/customer/social/callback

# X (Twitter)
X_CLIENT_ID=your_client_id
X_CLIENT_SECRET=your_client_secret
X_REDIRECT_URI=http://localhost:8000/api/v1/customer/social/callback

# Meta (Facebook & Instagram)
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_REDIRECT_URI=http://localhost:8000/api/v1/customer/social/callback

# YouTube
YOUTUBE_CLIENT_ID=your_client_id
YOUTUBE_CLIENT_SECRET=your_client_secret
YOUTUBE_REDIRECT_URI=http://localhost:8000/api/v1/customer/social/callback

# ... (add for other platforms as needed)
```

### How to Get API Credentials

See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for detailed setup instructions for each platform.

---

## 📘 API Endpoints

### Authentication
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/customer/signup` | Register new customer | No |
| POST | `/api/v1/customer/login` | Customer login | No |
| POST | `/api/v1/customer/logout` | Invalidate token | Yes |
| POST | `/api/v1/customer/refresh` | Refresh JWT token | Yes |
| GET | `/api/v1/customer/profile` | Get customer profile | Yes |

### Social Media Integration
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/customer/social/auth-url` | Get OAuth URL for platform | Yes |
| GET | `/api/v1/customer/social/callback` | OAuth callback handler | No |
| GET | `/api/v1/customer/social/accounts` | List connected accounts | Yes |
| DELETE | `/api/v1/customer/social/disconnect/{id}` | Disconnect account | Yes |

### Post Management
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/customer/posts/upload` | Upload media files | Yes |
| POST | `/api/v1/customer/posts/create` | Create post draft | Yes |
| POST | `/api/v1/customer/posts/{id}/publish` | Publish to platforms | Yes |
| GET | `/api/v1/customer/posts` | List all posts | Yes |
| GET | `/api/v1/customer/posts/{id}` | Get post details | Yes |
| PUT | `/api/v1/customer/posts/{id}` | Update post | Yes |
| DELETE | `/api/v1/customer/posts/{id}` | Delete post | Yes |

### Analytics
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/customer/posts/{id}/stats` | Get post analytics | Yes |
| GET | `/api/v1/customer/social/{id}/insights` | Get platform insights | Yes |

---

## 🧩 Core Architectural Principles

### ✅ Clean Separation of Concerns

- **Business logic** is extracted into Service classes, organized by version (e.g., `App\Services\Api\V1\`)
- All services implement contracts (interfaces) and are bound via Laravel's container
- **Controllers** only handle HTTP request/response logic
- **Services** contain all business logic
- **Contracts** define expected service behaviors
- Each layer is testable and independently swappable

### 🛠️ Custom Artisan Commands

```bash
php artisan make:service Api/V1/Payment
```

**What it does:**
- Creates `PaymentInterface.php` inside `App\Contracts\Api\V1\`
- Creates `PaymentService.php` inside `App\Services\Api\V1\`
- Automatically binds the interface to the service in `ServiceBindingProvider.php`

### 🔐 Security Features

- **JWT Authentication** for customer sessions
- **OAuth 2.0/1.0a** for social media platforms
- **PKCE (Proof Key for Code Exchange)** for enhanced security
- **CSRF Protection** with state parameter
- **Scope Verification** before publishing
- **Rate Limiting** awareness per platform
- **Content Validation** (file size, type, duration)
- **Token Auto-Refresh** when expiring
- **Error Tracking** (3 errors = account suspension)

---

## 🛡️ Middleware

### Customer Authentication
- `JwtCustomerAuthMiddleware` - Validates JWT token

### Subscription Validation
- `SubscriptionValidationMiddleware` - Checks plan limits

### Social Media Security
- `SocialMediaSecurityMiddleware` - Validates platform permissions and rate limits

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

**Test Coverage Includes:**
- Customer authentication (signup, login, logout, refresh)
- Social OAuth flow (connect, callback, disconnect)
- Post creation and publishing
- Multi-platform publishing
- Subscription validation
- Security middleware
- Content validation

---

## 📦 Deployment Notes

### Production Checklist

- [ ] Configure `.env` with production values
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate application key: `php artisan key:generate`
- [ ] Run optimizations:
```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
      php artisan optimize 
```
- [ ] Set up queue worker (Redis/SQS recommended)
- [ ] Configure social media API credentials
- [ ] Set up HTTPS for OAuth callbacks
- [ ] Configure storage (S3/Cloud Storage for media)
- [ ] Set up monitoring and logging
- [ ] Configure rate limiting
- [ ] Set up database backups

### Environment Variables Security

**Secure these variables:**
- `APP_KEY`
- `JWT_SECRET`
- All social media `CLIENT_SECRET` values
- Database credentials
- Mail server credentials

---

## 📊 Platform-Specific Notes

### TikTok
- PKCE is **MANDATORY** (S256 method)
- 3-step upload: init → upload → poll status
- Max 287MB, 3-600 seconds duration

### X (Twitter)
- Media upload **requires OAuth 1.0a** (v1.1 API)
- Text-only can use OAuth 2.0 (v2 API)
- Free tier: 17 posts/day limit

### Instagram
- Requires **Business or Creator** account
- Media MUST be on **public CDN URL** (cannot upload file directly)
- Container method: create → wait → publish

### YouTube
- **Must use direct cURL** for multipart/related upload
- Laravel Http client cannot handle multipart/related
- Videos initially uploaded as private

### More Details
See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for complete platform documentation.

---

## 🤝 Contributing

- Use consistent naming and PSR-12 formatting
- Cover all new features with proper tests
- Follow Meedyo commit message format
- Submit PRs with updated API docs
- Run `php artisan pint` before committing

---

## 📚 Additional Documentation

- **[API_DOCUMENTATION.md](./API_DOCUMENTATION.md)** - Complete API reference for all 21 platforms
- **OpenAPI Spec:** `storage/api-docs/api-docs.json`
- **Postman Collection:** Available on request

---

## 📝 License

This project is proprietary software. All rights reserved.

---

> 🏗️ Built with ❤️ by [Meedyo Team](https://engagepro-nine.vercel.app/) – Empowering creators worldwide

**Status:** ✅ **Production Ready** (October 2025)  
**Platforms:** 21 integrated and verified  
**API Version:** v1  
**Laravel Version:** 11.x
