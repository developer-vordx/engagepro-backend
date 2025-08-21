<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdateProfileInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use App\Helper;

class UpdateProfileService implements UpdateProfileInterface
{
    /**
     * @param $request
     * @return JsonResponse
     */
    public function handle($request): JsonResponse
    {
        try {
            DB::beginTransaction();
            Customer::find($request->customer->id)->update([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);
            DB::commit();
            return Helper::response(
                'Profile Updated',
                'Profile updated successfully',
                ResponseAlias::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }
}
