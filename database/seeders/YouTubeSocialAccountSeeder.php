<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SocialAccount;

class YouTubeSocialAccountSeeder extends Seeder
{
    public function run(): void
    {
        SocialAccount::updateOrCreate(
            ['slug' => 'youtube'],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'url' => 'https://www.googleapis.com/youtube/v3',
                'scopes' => [
                    'https://www.googleapis.com/auth/youtube',
                    'https://www.googleapis.com/auth/youtube.upload',
                    'https://www.googleapis.com/auth/youtube.force-ssl',
                    'https://www.googleapis.com/auth/youtubepartner'
                ],
                'supported_media_types' => [
                    'video/mp4',
                    'video/mov',
                    'video/avi',
                    'video/wmv',
                    'video/flv',
                    'video/webm',
                    'video/mkv'
                ],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
