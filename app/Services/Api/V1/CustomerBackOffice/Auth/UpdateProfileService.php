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
            
            $updateData = [
                'name' => $request->name,
                'phone' => $request->phone,
            ];
            
            // Add optional fields if provided
            if ($request->has('company')) {
                $updateData['company'] = $request->company;
            }
            
            if ($request->has('timezone')) {
                $updateData['timezone'] = $request->timezone;
            }
            
            $customer = Customer::find($request->customer->id);
            $customer->update($updateData);
            
            DB::commit();
            
            // Return updated customer data
            return Helper::response(
                [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'company' => $customer->company,
                    'timezone' => $customer->timezone,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ],
                ResponseAlias::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }
}
