<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Analytics;

use App\Contracts\Api\V1\CustomerBackOffice\Analytics\GetOverviewInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected GetOverviewInterface $getOverview;

    public function __construct(GetOverviewInterface $getOverview)
    {
        $this->getOverview = $getOverview;
    }

    public function overview(Request $request)
    {
        return $this->getOverview->handle($request);
    }
}

