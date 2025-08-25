<?php

namespace Database\Seeders;

use App\Models\SocialAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocialAccountSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'url' => 'https://open-api.tiktok.com',
                'scopes' => json_encode(['user.info.basic', 'video.upload']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '1GB', 'formats' => ['mp4', 'mov'], 'max_duration' => 180]
                ]),
                'status' => 'active',
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'url' => 'https://www.googleapis.com/youtube/v3',
                'scopes' => json_encode(['https://www.googleapis.com/auth/youtube.upload']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '256GB', 'formats' => ['mp4', 'mov', 'avi'], 'max_duration' => 43200]
                ]),
                'status' => 'pending',
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'url' => 'https://graph.instagram.com',
                'scopes' => json_encode(['instagram_basic', 'instagram_content_publish']),
                'supported_media_types' => json_encode(['image', 'video']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '4GB', 'formats' => ['mp4'], 'max_duration' => 60]
                ]),
                'status' => 'pending',
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'url' => 'https://graph.facebook.com',
                'scopes' => json_encode(['pages_manage_posts', 'pages_read_engagement']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '10GB', 'formats' => ['mp4'], 'max_duration' => 7200]
                ]),
                'status' => 'pending',
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'url' => 'https://api.linkedin.com',
                'scopes' => json_encode(['w_member_social']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '20MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '5GB', 'formats' => ['mp4'], 'max_duration' => 600]
                ]),
                'status' => 'pending',
            ],
            [
                'name' => 'Pinterest',
                'slug' => 'pinterest',
                'url' => 'https://api.pinterest.com',
                'scopes' => json_encode(['pins:write']),
                'supported_media_types' => json_encode(['image']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '32MB', 'formats' => ['jpg', 'png']]
                ]),
                'status' => 'pending',
            ],
            [
                'name' => 'X (Twitter)',
                'slug' => 'x',
                'url' => 'https://api.twitter.com',
                'scopes' => json_encode(['tweet.write', 'users.read']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '5MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '512MB', 'formats' => ['mp4'], 'max_duration' => 140]
                ]),
                'status' => 'pending',
            ],
        ];

        foreach ($platforms as $platform) {
            DB::table('social_accounts')->insert(array_merge($platform, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
