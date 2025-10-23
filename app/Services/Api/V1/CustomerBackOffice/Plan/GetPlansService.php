<?php

namespace App\Services\Api\V1\CustomerBackOffice\Plan;

use App\Contracts\Api\V1\CustomerBackOffice\Plan\GetPlansInterface;
use App\Models\Plan;
use App\Utils\BaseService;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class GetPlansService extends BaseService implements GetPlansInterface
{
    public function handle($request)
    {
        try {
            $plans = Plan::where('is_active', true)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'description',
                    'price',
                    'type',
                    'duration',
                    'is_active',
                    'priority_support',
                    'custom_branding',
                    'api_access',
                    'max_accounts_per_platform',
                    'max_posts_per_month',
                    'max_file_size_mb',
                    'analytics_retention_days',
                    'created_at'
                ])
                ->orderBy('price')
                ->get();
            
            return Helper::response($plans, ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

