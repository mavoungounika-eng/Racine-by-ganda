<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\CrmAiService;
use Illuminate\Http\Request;

class CrmAiController extends Controller
{
    public function customerBehavior(int $id, CrmAiService $service)
    {
        $user = User::findOrFail($id);
        return response()->json($service->analyzeCustomerBehavior($user));
    }

    public function segmentSuggestions(CrmAiService $service)
    {
        $customers = User::where('role', 'client')->get();
        return response()->json($service->suggestSegments($customers));
    }
}
