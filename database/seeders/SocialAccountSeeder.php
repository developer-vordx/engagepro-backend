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
                'base_url' => 'https://open-api.tiktok.com',
                'required_scopes' => json_encode(['user.info.basic', 'video.upload']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '1GB', 'formats' => ['mp4', 'mov'], 'max_duration' => 180]
                ]),
                'supports_scheduling' => false,
                'status' => 'active',
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'base_url' => 'https://www.googleapis.com/youtube/v3',
                'required_scopes' => json_encode(['https://www.googleapis.com/auth/youtube.upload']),
                'supported_media_types' => json_encode(['video']),
                'media_requirements' => json_encode([
                    'video' => ['max_size' => '256GB', 'formats' => ['mp4', 'mov', 'avi'], 'max_duration' => 43200]
                ]),
                'supports_scheduling' => true,
                'status' => 'pending',
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'base_url' => 'https://graph.instagram.com',
                'required_scopes' => json_encode(['instagram_basic', 'instagram_content_publish']),
                'supported_media_types' => json_encode(['image', 'video']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '4GB', 'formats' => ['mp4'], 'max_duration' => 60]
                ]),
                'supports_scheduling' => true,
                'status' => 'pending',
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'base_url' => 'https://graph.facebook.com',
                'required_scopes' => json_encode(['pages_manage_posts', 'pages_read_engagement']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '30MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '10GB', 'formats' => ['mp4'], 'max_duration' => 7200]
                ]),
                'supports_scheduling' => true,
                'status' => 'pending',
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'base_url' => 'https://api.linkedin.com',
                'required_scopes' => json_encode(['w_member_social']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '20MB', 'formats' => ['jpg', 'png']],
                    'video' => ['max_size' => '5GB', 'formats' => ['mp4'], 'max_duration' => 600]
                ]),
                'supports_scheduling' => false,
                'status' => 'pending',
            ],
            [
                'name' => 'Pinterest',
                'slug' => 'pinterest',
                'base_url' => 'https://api.pinterest.com',
                'required_scopes' => json_encode(['pins:write']),
                'supported_media_types' => json_encode(['image']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '32MB', 'formats' => ['jpg', 'png']]
                ]),
                'supports_scheduling' => false,
                'status' => 'pending',
            ],
            [
                'name' => 'X (Twitter)',
                'slug' => 'x',
                'base_url' => 'https://api.twitter.com',
                'required_scopes' => json_encode(['tweet.write', 'users.read']),
                'supported_media_types' => json_encode(['image', 'video', 'text']),
                'media_requirements' => json_encode([
                    'image' => ['max_size' => '5MB', 'formats' => ['jpg', 'png', 'gif']],
                    'video' => ['max_size' => '512MB', 'formats' => ['mp4'], 'max_duration' => 140]
                ]),
                'supports_scheduling' => false,
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
