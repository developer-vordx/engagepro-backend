<?php

namespace Database\Seeders;

use App\Models\SocialAccount;
use Illuminate\Database\Seeder;

class SocialAccountSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            // Tier 1: Fully implemented with OAuth 2.0/1.0a and publishing
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'url' => 'https://open.tiktokapis.com',
                'scopes' => json_encode(['user.info.basic', 'user.info.profile', 'user.info.stats', 'video.list', 'video.upload', 'video.publish']),
                'supported_media_types' => json_encode(['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '287MB', 'formats' => ['mp4', 'mov', 'avi', 'webm'], 'min_duration' => 3, 'max_duration' => 600]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'X (Twitter)',
                'slug' => 'x',
                'url' => 'https://api.twitter.com',
                'scopes' => json_encode(['tweet.read', 'tweet.write', 'tweet.moderate.write', 'users.read', 'follows.read', 'follows.write', 'offline.access', 'space.read', 'mute.read', 'mute.write', 'like.read', 'like.write', 'list.read', 'list.write', 'block.read', 'block.write', 'bookmark.read', 'bookmark.write']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '5MB', 'formats' => ['jpg', 'png', 'gif', 'webp']],
                    'video' => ['max_size' => '512MB', 'formats' => ['mp4'], 'max_duration' => 140],
                    'text' => ['max_length' => 280]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Meta (Facebook & Instagram)',
                'slug' => 'meta',
                'url' => 'https://graph.facebook.com',
                'scopes' => json_encode(['email', 'public_profile', 'pages_show_list', 'pages_manage_posts', 'pages_read_engagement', 'instagram_basic', 'instagram_content_publish', 'instagram_manage_comments', 'instagram_manage_insights']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '10GB', 'formats' => ['mp4'], 'max_duration' => 7200]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'url' => 'https://graph.facebook.com',
                'scopes' => json_encode(['pages_manage_posts', 'pages_read_engagement', 'publish_to_groups']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '10GB', 'formats' => ['mp4'], 'max_duration' => 7200]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'url' => 'https://graph.instagram.com',
                'scopes' => json_encode(['instagram_basic', 'instagram_content_publish', 'instagram_manage_comments', 'instagram_manage_insights']),
                'supported_media_types' => json_encode(['image', 'video']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '4GB', 'formats' => ['mp4'], 'min_duration' => 3, 'max_duration' => 60]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'url' => 'https://www.googleapis.com/youtube/v3',
                'scopes' => json_encode([
                    'https://www.googleapis.com/auth/youtube',
                    'https://www.googleapis.com/auth/youtube.upload',
                    'https://www.googleapis.com/auth/youtube.force-ssl',
                    'https://www.googleapis.com/auth/youtube.readonly',
                    'https://www.googleapis.com/auth/youtubepartner'
                ]),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '128GB', 'formats' => ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'], 'max_duration' => 43200]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'url' => 'https://api.linkedin.com',
                'scopes' => json_encode(['openid', 'profile', 'email', 'w_member_social', 'w_organization_social']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '20MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '5GB', 'formats' => ['mp4'], 'max_duration' => 600]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Reddit',
                'slug' => 'reddit',
                'url' => 'https://oauth.reddit.com',
                'scopes' => json_encode(['identity', 'submit', 'read', 'vote', 'save', 'edit', 'history']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '20MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '1GB', 'formats' => ['mp4', 'gif']]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Pinterest',
                'slug' => 'pinterest',
                'url' => 'https://api.pinterest.com',
                'scopes' => json_encode(['boards:read', 'boards:write', 'pins:read', 'pins:write', 'user_accounts:read']),
                'supported_media_types' => json_encode(['image']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '32MB', 'formats' => ['jpg', 'png']]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Vimeo',
                'slug' => 'vimeo',
                'url' => 'https://api.vimeo.com',
                'scopes' => json_encode(['public', 'private', 'upload', 'edit', 'delete', 'interact', 'stats']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '128GB', 'formats' => ['mp4', 'mov', 'avi', 'wmv', 'flv']]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Dailymotion',
                'slug' => 'dailymotion',
                'url' => 'https://api.dailymotion.com',
                'scopes' => json_encode(['userinfo', 'email', 'manage_videos', 'manage_playlists', 'manage_comments']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '4GB', 'formats' => ['mp4', 'mov', 'avi', 'wmv'], 'max_duration' => 86400]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Tumblr',
                'slug' => 'tumblr',
                'url' => 'https://api.tumblr.com/v2',
                'scopes' => json_encode(['basic', 'write', 'offline_access']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '10MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '500MB', 'formats' => ['mp4']]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Mastodon',
                'slug' => 'mastodon',
                'url' => 'https://mastodon.social',
                'scopes' => json_encode(['read', 'write', 'write:statuses', 'write:media', 'follow']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '8MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '40MB', 'formats' => ['mp4', 'webm']]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Twitch',
                'slug' => 'twitch',
                'url' => 'https://api.twitch.tv/helix',
                'scopes' => json_encode(['user:read:email', 'channel:manage:broadcast', 'channel:read:stream_key', 'clips:edit']),
                'supported_media_types' => json_encode(['text']),
                'media_requirements' => json_encode([]),
                'status' => 'active',
            ],
            
            // Tier 2: Additional platforms - fully implemented, requires credentials
            [
                'name' => 'Threads',
                'slug' => 'threads',
                'url' => 'https://graph.threads.net',
                'scopes' => json_encode(['threads_basic', 'threads_content_publish', 'threads_manage_insights', 'threads_manage_replies']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '1GB', 'formats' => ['mp4'], 'max_duration' => 300],
                    'text' => ['max_length' => 500],
                    'requires_public_url' => true
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Telegram',
                'slug' => 'telegram',
                'url' => 'https://api.telegram.org',
                'scopes' => json_encode(['bot', 'sendMessage', 'sendPhoto', 'sendVideo', 'sendDocument']),
                'supported_media_types' => json_encode(['image', 'video', 'text', 'document']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '10MB', 'formats' => ['jpg', 'png', 'gif', 'webp']],
                    'video' => ['max_size' => '50MB', 'formats' => ['mp4']],
                    'text' => ['max_length' => 4096],
                    'caption' => ['max_length' => 1024]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Bluesky',
                'slug' => 'bluesky',
                'url' => 'https://bsky.social',
                'scopes' => json_encode(['app_password', 'post', 'follow', 'like', 'repost']),
                'supported_media_types' => json_encode(['image', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '1MB', 'formats' => ['jpg', 'png', 'gif']],
                    'text' => ['max_length' => 300],
                    'max_images' => 4
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'Snapchat',
                'slug' => 'snapchat',
                'url' => 'https://accounts.snapchat.com',
                'scopes' => json_encode([
                    'https://auth.snapchat.com/oauth2/api/user.display_name',
                    'https://auth.snapchat.com/oauth2/api/user.external_id',
                    'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar'
                ]),
                'supported_media_types' => json_encode([]),
                'media_requirements' => json_encode([
                    'note' => 'Login Kit is for authentication only, not content posting'
                ]),
                'status' => 'active',
            ],
            
        ];

        foreach ($platforms as $platform) {
            SocialAccount::updateOrCreate(
                ['slug' => $platform['slug']],
                array_merge($platform, [
                'created_at' => now(),
                'updated_at' => now(),
                ])
            );
        }
    }
}
