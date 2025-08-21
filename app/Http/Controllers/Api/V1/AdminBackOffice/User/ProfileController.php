<?php

namespace App\Http\Controllers\Api\V1\AdminBackOffice\User;

use App\Contracts\Api\V1\AdminBackOffice\User\ProfileInterface;
use App\Contracts\Api\V1\AdminBackOffice\User\UpdateEmailInterface;
use App\Contracts\Api\V1\AdminBackOffice\User\UpdatePasswordInterface;
use App\Contracts\Api\V1\AdminBackOffice\User\UserProfileUpdateInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminBackOffice\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\AdminBackOffice\User\UpdateEmailRequest;
use App\Http\Requests\Api\V1\AdminBackOffice\User\UserProfileUpdateRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    protected ProfileInterface $profile;
    protected UserProfileUpdateInterface $userProfileUpdate;
    protected UpdatePasswordInterface $updatePassword;
    protected UpdateEmailInterface $updateEmail;

    public function __construct(ProfileInterface $profile, UserProfileUpdateInterface $userProfileUpdate, UpdatePasswordInterface $updatePassword, UpdateEmailInterface $updateEmail)
    {
        $this->profile = $profile;
        $this->userProfileUpdate = $userProfileUpdate;
        $this->updatePassword = $updatePassword;
        $this->updateEmail = $updateEmail;
    }

    public function getProfile(Request $request)
    {
        return $this->profile->handle($request);
    }

    public function updateProfile(UserProfileUpdateRequest $request)
    {
        return $this->userProfileUpdate->handle($request);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        return $this->updatePassword->handle($request);
    }

    public function updateEmail(UpdateEmailRequest $request)
    {
        return $this->updateEmail->handle($request);
    }

}
