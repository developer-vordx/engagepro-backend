<?php

namespace App\Services\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\GetAuthUrlInterface;
use App\Models\CustomerAccount;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Support\Facades\Auth;
use App\Helper;

class GetAuthUrlService implements GetAuthUrlInterface
{
    private SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    public function handle($request, $platform)
    {
        try {
            $customer = Auth::guard('customer')->user();

            // Check if user can add more accounts for this platform
            if (!$this->canAddAccount($customer, $platform)) {
                return Helper::response(
                    'Account limit reached for this platform. Upgrade your subscription to add more accounts.',
                    ResponseAlias::HTTP_FORBIDDEN);
            }

            $service = $this->socialMediaManager->getService($platform);
            if (!$service){
                return Helper::response("Platform '{$platform}' is not supported,", ResponseAlias::HTTP_NOT_ACCEPTABLE);
            }
            $authUrl = $service->getAuthorizationUrl();

            return Helper::response([
                    'auth_url' => $authUrl,
                    'platform' => $platform
                ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Check if user can add more accounts for a platform
     * @param $customer
     * @param $platform
     * @return bool
     */
    private function canAddAccount($customer, $platform): bool
    {
        $currentCount = CustomerAccount::whereHas('socialAccount', function ($q) use ($platform) {
            $q->where('slug', $platform);
        })
            ->where('customer_id', $customer->id)
            ->count();

        $maxAccounts = $customer->subscription?->plan->max_accounts_per_platform ?? 1;

        return $currentCount < $maxAccounts;
    }
}
