<?php

namespace App\Services\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\GetPlatformsInterface;
use App\Models\SocialAccount;
use App\Utils\BaseService;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class GetPlatformsService extends BaseService implements GetPlatformsInterface
{
    public function handle($request)
    {
        try {
            $platforms = SocialAccount::where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function($platform) {
                    return [
                        'id' => $platform->id,
                        'name' => $platform->name,
                        'slug' => $platform->slug,
                        'url' => $platform->url,
                        'icon_url' => $platform->icon_url,
                        'status' => $platform->status,
                        'supports_video' => true, // Default, can be customized
                        'supports_image' => true,
                        'supports_text' => true,
                    ];
                })
                ->values()
                ->toArray();
            
            return Helper::response($platforms, ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

