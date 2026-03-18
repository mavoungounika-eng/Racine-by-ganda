<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Services\Cms\ContentBlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentBlockController extends Controller
{
    protected $blockService;

    public function __construct(ContentBlockService $blockService)
    {
        $this->blockService = $blockService;
    }

    public function index()
    {
        return response()->json(ContentBlock::all());
    }

    public function show(string $key)
    {
        $block = ContentBlock::where('key', $key)->firstOrFail();
        return response()->json($block);
    }

    public function update(Request $request, string $key)
    {
        $request->validate([
            'content' => 'required',
            'title' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->blockService->updateBlock($key, $request->content, Auth::id());
        
        if ($request->has('title') || $request->has('is_active')) {
            $block = ContentBlock::where('key', $key)->first();
            if ($block) {
                $block->update($request->only(['title', 'is_active']));
            }
        }

        return response()->json(['message' => 'Block updated successfully.']);
    }

    public function publicShow(string $key)
    {
        $block = $this->blockService->getBlock($key);
        abort_if(!$block, 404);
        return response()->json($block);
    }
}
