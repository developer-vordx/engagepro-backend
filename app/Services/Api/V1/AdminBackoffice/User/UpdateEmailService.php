<?php

namespace App\Services\Api\V1\AdminBackoffice\User;

use App\Contracts\Api\V1\AdminBackOffice\User\UpdateEmailInterface;
use App\Utils\BaseService;
use function App\Services\Api\V1\User\errors;
use function App\Services\Api\V1\User\success;

class UpdateEmailService extends  BaseService  implements UpdateEmailInterface
{
    public function handle($request)
    {
        try {
            $user = auth()->user();

            // Check if the new email is different
            if ($request->email === $user->email) {
                return errors('New email must be different from current email.', [],422);
            }

            // Optional: Verify OTP here
//             if (!OTPService::verify($user, $request->otp)) {
//                 return errors('Invalid OTP.', 422);
//             }

            // Update the email
            $user->email = $request->email;
            $user->save();

            return success('Email updated successfully.', $user);
        } catch (\Exception $exception) {
            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(), 500);
        }
    }
}
