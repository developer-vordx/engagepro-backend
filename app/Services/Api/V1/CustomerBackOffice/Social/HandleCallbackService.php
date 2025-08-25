<?php

namespace App\Services\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface;
use App\Models\SocialAccount;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAccount;
use Illuminate\Http\Response;
use App\Helper;

class HandleCallbackService implements HandleCallbackInterface
{
    private SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    public function handle($request, $platform)
    {
        try {
            $user = Auth::guard('customer')->user();
            $service = $this->socialMediaManager->getService($platform);
            // Exchange code for token
            $tokenData = $service->exchangeCodeForToken($request->code);

            if (isset($tokenData['header_code'])){
                return Helper::response(
                    ResponseAlias::$statusTexts[$tokenData['header_code']],
                    $tokenData['body'],$tokenData['header_code']
                );
            }

            // Get user profile from platform
            $profileData = $service->getUserProfile($tokenData['access_token']);
            if (isset($profileData['header_code'])){
                return Helper::response(
                    ResponseAlias::$statusTexts[$profileData['header_code']],
                    $profileData['body'],$profileData['header_code']
                );
            }
            // Check if this account is already linked to another user
            $existingAccount = CustomerAccount::whereHas('socialAccount', function ($q) use ($platform) {
                $q->where('slug', $platform);
            })->where('identifier', $profileData['identifier'])
                ->first();

            if ($existingAccount && $existingAccount->user_id !== $user->id) {
                return Helper::response(
                    Response::$statusTexts[ResponseAlias::HTTP_CONFLICT],
                    'This social media account is already linked to another user.',
                    ResponseAlias::HTTP_CONFLICT
                );
            }

            DB::beginTransaction();
            // Create or update social account
            $id = SocialAccount::where('slug', $platform)->first();
            $socialAccount = CustomerAccount::updateOrCreate(
                [
                    'social_accounts_id' => $id->id,
                    'customer_id' => $user->id,
                    'identifier' => $profileData['identifier'],
                ], [
                    'username' => $profileData['username'] ?? 'no name',
                    'display_name' => $profileData['username'] ?? 'no name',
                    'profile_picture' => $profileData['profile_picture'] ?? '123',
                    'follower_count' => $profileData['follower_count'] ?? 'asd',
                    'following_count' => $profileData['following_count'] ?? 'asd',
                    'access_token' => $tokenData['access_token'] ?? 'asdasd',
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );
            DB::commit();
            return Helper::response(
                ResponseAlias::$statusTexts[ResponseAlias::HTTP_OK],
                [
                    'account' => [
                        'id' => $socialAccount->id,
                        'platform' => $platform,
                        'username' => $socialAccount->username,
                        'profile_picture' => $socialAccount->profile_picture,
                        'follower_count' => $socialAccount->follower_count,
                        'is_active' => $socialAccount->is_active,
                    ]
                ],
                ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }
}
