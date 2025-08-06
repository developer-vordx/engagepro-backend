<?php

namespace App\Rules\Api\V1\CustomerBackOffice\Auth;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class LoginRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $checkToken = DB::table('password_reset_token_customers')
            ->where($attribute, $value)->exists();

        $checkUserStatus = User::where($attribute, $value)->where('status', 0)->exists();

        if ($checkToken) {
            $fail('Please set your password first.');
        }
        if ($checkUserStatus) {
            $fail('Your account is not active. Please contact customer support.');
        }
    }
}
