<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\Cms\BannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    protected $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }

    public function index(Request $request)
    {
        $query = Banner::query();

        if ($request->has('position')) {
            $query->forPosition($request->position);
        }

        return $query->orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image_path' => 'required|string',
            'mobile_image_path' => 'nullable|string',
            'link_url' => 'nullable|string',
            'link_text' => 'nullable|string',
            'link_target' => 'required|in:_self,_blank',
            'position' => 'required|in:homepage_hero,homepage_promo,category_top,sidebar',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'sort_order' => 'integer',
        ]);

        $validated['created_by'] = Auth::id();

        $banner = Banner::create($validated);
        $this->bannerService->invalidateBannerCache();

        return response()->json($banner, 201);
    }

    public function show($id)
    {
        return Banner::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image_path' => 'sometimes|required|string',
            'mobile_image_path' => 'nullable|string',
            'link_url' => 'nullable|string',
            'link_text' => 'nullable|string',
            'link_target' => 'sometimes|required|in:_self,_blank',
            'position' => 'sometimes|required|in:homepage_hero,homepage_promo,category_top,sidebar',
            'status' => 'sometimes|required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'sort_order' => 'integer',
        ]);

        $banner->update($validated);
        $this->bannerService->invalidateBannerCache();

        return response()->json($banner);
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        $this->bannerService->invalidateBannerCache();

        return response()->json(null, 204);
    }

    public function publicIndex(string $position)
    {
        return response()->json($this->bannerService->getActiveBanners($position));
    }

    public function click(int $id)
    {
        $this->bannerService->recordClick($id);
        return response()->json(['success' => true]);
    }
}
