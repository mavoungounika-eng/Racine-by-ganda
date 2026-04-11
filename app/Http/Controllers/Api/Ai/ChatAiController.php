<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\CreatorChatService;
use Illuminate\Http\Request;

class ChatAiController extends Controller
{
    public function chat(Request $request, CreatorChatService $service)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|integer',
        ]);

        $response = $service->chat(
            auth()->user(),
            $request->message,
            $request->conversation_id
        );

        return response()->json($response);
    }

    public function conversations()
    {
        $conversations = AiConversation::where('user_id', auth()->id())
            ->orderBy('last_message_at', 'desc')
            ->paginate(10);

        return response()->json($conversations);
    }

    public function showConversation(int $id)
    {
        $conversation = AiConversation::where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($conversation);
    }

    public function deleteConversation(int $id)
    {
        $conversation = AiConversation::where('user_id', auth()->id())
            ->findOrFail($id);

        $conversation->delete();

        return response()->json(['success' => true]);
    }
}
