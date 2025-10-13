# EngagePro Customer API Documentation

## Overview
EngagePro is a comprehensive social media management platform that allows customers to connect their social media accounts and publish content across multiple platforms from a single interface.

## Supported Platforms
- **TikTok** - Video sharing platform
- **X (Twitter)** - Microblogging and social networking
- **Meta (Facebook & Instagram)** - Social networking and photo/video sharing
- **YouTube** - Video hosting and sharing

## Architecture

### Authentication
- JWT-based authentication for customers
- Separate guard: `customer`
- Email verification required
- Password reset functionality

### Core Components

#### 1. Middleware
- **`JwtCustomerAuthMiddleware`** - Validates customer authentication
- **`SubscriptionValidationMiddleware`** - Validates subscription plan and feature access
- **`SocialMediaSecurityMiddleware`** - Rate limiting and account protection
- **`RequestLogMiddleware`** - Logs all API requests

#### 2. Services

##### Authentication Services
- `LoginService` - Handle customer login
- `SignUpService` - Handle customer registration
- `GetAuthUserService` - Get authenticated user info
- `UpdateProfileService` - Update customer profile
- `UpdatePasswordService` - Change password
- `LogoutService` - Handle logout
- `VerifyEmailService` - Email verification
- `SetPasswordService` - Set password after forgot password

##### Social Media Services
- `GetAuthUrlService` - Generate OAuth URLs for platform authentication
- `HandleCallbackService` - Process OAuth callbacks
- `TikTokService` - TikTok API integration
- `XService` - X (Twitter) API integration
- `MetaService` - Meta (Facebook/Instagram) API integration
- `YouTubeService` - YouTube API integration

##### Post Management Services
- `UploadPostService` - Handle file uploads with subscription validation
- `PublishPostService` - Publish posts to social media platforms
- `PostStatsService` - Get post analytics and statistics

#### 3. Models

##### Core Models
- **Customer** - Customer account information
- **CustomerPlan** - Active subscription plans for customers
- **Plan** - Available subscription plans
- **PlanFeature** - Features available in each plan

##### Social Media Models
- **SocialAccount** - Platform configurations (TikTok, X, Meta, YouTube)
- **CustomerAccount** - Customer's connected social media accounts

##### Content Models
- **Post** - Customer posts
- **PostFile** - Uploaded media files
- **SocialPost** - Published posts on social platforms
- **PostInsight** - Analytics data for posts

## API Endpoints

### Public Endpoints

#### Authentication
```
POST /api/v1/signup
POST /api/v1/login
POST /api/v1/forgot-password
POST /api/v1/set-password
POST /api/v1/verify-email
```

#### OAuth
```
GET /api/v1/google
GET /api/v1/google/callback
```

### Protected Endpoints (Require Authentication)

#### Customer Profile
```
GET    /api/v1/customerBackOffice/authenticate
POST   /api/v1/customerBackOffice/update-profile
POST   /api/v1/customerBackOffice/logout
POST   /api/v1/customerBackOffice/update-password
```

#### Social Media Integration
```
GET    /api/v1/customerBackOffice/social/{platform}/auth-url
POST   /api/v1/customerBackOffice/social/{platform}/callback
```
**Platforms:** `tiktok`, `x`, `meta`, `youtube`

#### Post Management

##### Basic CRUD
```
GET    /api/v1/customerBackOffice/posts
POST   /api/v1/customerBackOffice/posts
GET    /api/v1/customerBackOffice/posts/{id}
PUT    /api/v1/customerBackOffice/posts/{id}
DELETE /api/v1/customerBackOffice/posts/{id}
```

##### Publishing
```
POST   /api/v1/customerBackOffice/posts/{id}/publish
POST   /api/v1/customerBackOffice/posts/{id}/publish-social
       Middleware: subscription.validate:post, social.security
```

##### Upload (Requires Subscription)
```
POST   /api/v1/customerBackOffice/posts/upload
       Middleware: subscription.validate:post
```

##### Analytics
```
GET    /api/v1/customerBackOffice/posts/{id}/analytics
GET    /api/v1/customerBackOffice/posts/stats/overview
       Middleware: subscription.validate:analytics
```

##### Subscription
```
GET    /api/v1/customerBackOffice/posts/subscription/info
```

## Security Features

### Rate Limiting
Platform-specific rate limits to prevent account blocking:
- **TikTok:** 5 posts per hour
- **X (Twitter):** 3 posts per hour
- **Instagram:** 4 posts per hour
- **Facebook:** 6 posts per hour
- **YouTube:** 2 posts per hour

### Subscription Validation
- Monthly post limits based on plan
- File size limits based on plan
- Analytics access control
- API access control

### Account Protection
- Error tracking (3 errors = temporary suspension)
- Content validation per platform
- Automatic rate limit enforcement
- OAuth token refresh handling

## Request/Response Examples

### 1. Sign Up
**Request:**
```json
POST /api/v1/signup
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "password": "SecurePass123!@",
    "password_confirmation": "SecurePass123!@"
}
```

**Response:**
```json
{
    "message": "Created",
    "data": {
        "message": "Please check your inbox and verify your email."
    }
}
```

### 2. Login
**Request:**
```json
POST /api/v1/login
{
    "email": "john@example.com",
    "password": "SecurePass123!@"
}
```

**Response:**
```json
{
    "message": "OK",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
    }
}
```

### 3. Get OAuth URL
**Request:**
```
GET /api/v1/customerBackOffice/social/tiktok/auth-url
Authorization: Bearer {token}
```

**Response:**
```json
{
    "message": "OK",
    "data": {
        "auth_url": "https://www.tiktok.com/auth/authorize/?client_key=..."
    }
}
```

### 4. Upload Post
**Request:**
```
POST /api/v1/customerBackOffice/posts/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "title": "My Awesome Video",
    "description": "Check out this amazing content!",
    "tags": ["awesome", "video", "content"],
    "target_platforms": ["tiktok", "youtube"],
    "files": [<file1>, <file2>]
}
```

**Response:**
```json
{
    "message": "Created",
    "data": {
        "message": "Post uploaded successfully",
        "post": {
            "id": 123,
            "title": "My Awesome Video",
            "status": "draft",
            "post_files": [...]
        }
    }
}
```

### 5. Publish to Social Media
**Request:**
```json
POST /api/v1/customerBackOffice/posts/123/publish-social
Authorization: Bearer {token}

{
    "platforms": ["tiktok", "youtube"],
    "publish_immediately": true
}
```

**Response:**
```json
{
    "message": "OK",
    "data": {
        "message": "Post publishing completed",
        "results": {
            "tiktok": {
                "success": true,
                "platform_post_id": "7123456789",
                "platform_url": "https://www.tiktok.com/@user/video/7123456789",
                "status": "published"
            },
            "youtube": {
                "success": true,
                "platform_post_id": "dQw4w9WgXcQ",
                "platform_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
                "status": "published"
            }
        }
    }
}
```

### 6. Get Statistics
**Request:**
```
GET /api/v1/customerBackOffice/posts/stats/overview
Authorization: Bearer {token}
```

**Response:**
```json
{
    "message": "OK",
    "data": {
        "subscription": {
            "plan_name": "Premium Plan",
            "posts_this_month": 15,
            "max_posts_per_month": 100,
            "posts_remaining": 85
        },
        "posts": {
            "total": 45,
            "published": 40,
            "draft": 3,
            "failed": 2
        },
        "platforms": {
            "tiktok": {
                "total_posts": 20,
                "successful_posts": 19,
                "failed_posts": 1
            }
        },
        "recent_activity": [...]
    }
}
```

## Error Responses

### Validation Error (422)
```json
{
    "message": "Unprocessable Entity",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 12 characters."]
    }
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthorized",
    "errors": "Invalid credentials provided"
}
```

### Forbidden (403)
```json
{
    "message": "Forbidden",
    "errors": "Monthly post limit reached. Upgrade your subscription."
}
```

### Rate Limit Exceeded (429)
```json
{
    "message": "Too Many Requests",
    "errors": "Rate limit exceeded for tiktok. Please wait before posting again."
}
```

## Subscription Plans

### Features by Plan
- **Post Limits** - Monthly post quota
- **File Size Limits** - Maximum file upload size
- **Analytics Access** - View post performance
- **API Access** - Programmatic access
- **Priority Support** - Faster support response
- **Custom Branding** - White-label options

### Plan Validation
All subscription-related features are validated via middleware:
- `subscription.validate:post` - Check post limits
- `subscription.validate:analytics` - Check analytics access
- `subscription.validate:api` - Check API access

## Platform-Specific Notes

### TikTok
- OAuth 2.0 with PKCE
- Video uploads supported
- Maximum video length varies by plan

### X (Twitter)
- OAuth 1.0a authentication
- 280 character limit for posts
- Supports images and videos

### Meta (Facebook/Instagram)
- OAuth 2.0 authentication
- Facebook Pages and Instagram Business accounts
- Different content requirements per platform

### YouTube
- OAuth 2.0 authentication
- Video uploads with metadata
- Channel management features

## Development Setup

### Environment Variables
```env
# TikTok
TIKTOK_CLIENT_ID=your_client_id
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URL=your_redirect_url

# X (Twitter)
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_CLIENT_ID=your_client_id
TWITTER_CLIENT_SECRET=your_client_secret
TWITTER_BEARER_TOKEN=your_bearer_token
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_access_token_secret

# Meta (Facebook/Instagram)
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_REDIRECT_URI=your_redirect_uri

# YouTube
YOUTUBE_CLIENT_ID=your_client_id
YOUTUBE_CLIENT_SECRET=your_client_secret
YOUTUBE_REDIRECT_URI=your_redirect_uri
```

## Best Practices

1. **Rate Limiting** - Always respect platform rate limits
2. **Content Validation** - Validate content before publishing
3. **Error Handling** - Handle API errors gracefully
4. **Token Refresh** - Implement automatic token refresh
5. **Security** - Never expose API credentials
6. **Testing** - Test with sandbox/test accounts first

## Support

For issues or questions:
1. Check error response messages
2. Review subscription limits
3. Verify platform authentication
4. Contact support if needed

