<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Plan;

use App\Contracts\Api\V1\CustomerBackOffice\Plan\GetPlansInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    protected GetPlansInterface $getPlans;

    public function __construct(GetPlansInterface $getPlans)
    {
        $this->getPlans = $getPlans;
    }

    public function index(Request $request)
    {
        return $this->getPlans->handle($request);
    }
}

