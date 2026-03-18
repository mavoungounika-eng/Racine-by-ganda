<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Product;
use App\Services\Ai\AdminAiService;
use App\Services\Ai\CrmAiService;
use App\Services\Ai\ErpAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAiController extends Controller
{
    public function dailySummary(AdminAiService $service)
    {
        return response()->json(['summary' => $service->generateDailySummary()]);
    }

    public function crmInsights(CrmAiService $service)
    {
        return response()->json(['insight' => $service->generateCrmInsight([])]);
    }

    public function stockAnomalies(ErpAiService $service)
    {
        $products = Product::all(); // Simplified, should be filtered in production
        return response()->json($service->detectStockAnomalies($products));
    }

    public function usage()
    {
        $usage = AiUsageLog::selectRaw('feature, 
            SUM(total_tokens) as tokens, 
            SUM(cost_usd) as cost, 
            COUNT(*) as calls')
            ->groupBy('feature')
            ->get();

        return response()->json($usage);
    }
}
