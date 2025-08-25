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
                    ResponseAlias::$statusTexts[ResponseAlias::HTTP_FORBIDDEN],
                    'Account limit reached for this platform. Upgrade your subscription to add more accounts.',
                    ResponseAlias::HTTP_FORBIDDEN);
            }

            $service = $this->socialMediaManager->getService($platform);
            if (!$service){
                return Helper::response(
                    ResponseAlias::$statusTexts[ResponseAlias::HTTP_NOT_ACCEPTABLE],
                    "Platform '{$platform}' is not supported,",
                    ResponseAlias::HTTP_NOT_ACCEPTABLE);
            }
            $authUrl = $service->getAuthorizationUrl();

            return Helper::response(
                ResponseAlias::$statusTexts[ResponseAlias::HTTP_OK],
                [
                    'auth_url' => $authUrl,
                    'platform' => $platform
                ],
                ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Check if user can add more accounts for a platform
     */
    private function canAddAccount($customer, string $platform): bool
    {
        $currentCount = CustomerAccount::whereHas('socialAccount', function ($q) use ($platform) {
            $q->where('slug', $platform);
        })
            ->where('customer_id', $customer->id)
            ->count();

        $maxAccounts = $customer->subscriptionPlan?->max_social_accounts_per_platform ?? 1;

        return $currentCount < $maxAccounts;
    }
}
