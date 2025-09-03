<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SocialAccount;

class MetaSocialAccountSeeder extends Seeder
{
    public function run(): void
    {
        SocialAccount::updateOrCreate(
            ['slug' => 'meta'],
            [
                'name' => 'Meta (Facebook & Instagram)',
                'slug' => 'meta',
                'url' => 'https://graph.facebook.com',
                'scopes' => [
                    'pages_read_engagement',
                    'pages_manage_posts',
                    'pages_show_list',
                    'instagram_basic',
                    'instagram_content_publish',
                    'instagram_manage_comments',
                    'instagram_manage_insights'
                ],
                'supported_media_types' => [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'video/mp4',
                    'video/mov',
                    'video/avi'
                ],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
