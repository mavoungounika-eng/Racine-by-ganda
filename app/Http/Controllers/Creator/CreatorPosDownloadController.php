<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\CreatorCapabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CreatorPosDownloadController extends Controller
{
    public function index(CreatorCapabilityService $caps): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $caps->can($user, 'can_use_pos')) {
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'L\'accès au POS Electron requiert le plan Signature.');
        }

        $version    = config('pos.version', '1.0.0');
        $releaseUrl = config('pos.release_url', '#');

        return view('creator.pos.download', compact('version', 'releaseUrl'));
    }
}
