<?php

namespace App\DTO\Api\V1\CustomerBackOffice\Auth;

use Illuminate\Support\Facades\Hash;
use App\Utils\BaseDTO;

class RegisterCustomerDTO extends BaseDTO
{
    public string $name;
    public string $email;
    public string $password;

    public function __construct($request)
    {
        $this->name = $request->name;
        $this->email = $request->email;
        $this->password = Hash::make($request->password);
    }
}
