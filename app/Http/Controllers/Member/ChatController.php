<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    /**
     * List all sessions for the current member, newest first.
     */
    public function index(Request $request)
    {
        $sessions = ChatSession::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'title'        => $s->title ?? 'New conversation',
                'last_message' => $s->messages->first()?->content,
                'last_at'      => $s->messages->first()?->created_at?->diffForHumans(),
                'message_count'=> $s->messages_count,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Create a new empty session.
     */
    public function store(Request $request)
    {
        $session = $this->chat->createSession($request->user());
        return response()->json(['session' => ['id' => $session->id, 'title' => 'New conversation']]);
    }

    /**
     * Load messages for a specific session.
     */
    public function show(Request $request, ChatSession $session)
    {
        abort_if($session->user_id !== $request->user()->id, 403);

        $messages = $session->messages()->orderBy('created_at')->get(['role', 'content', 'created_at']);

        return response()->json([
            'session'  => ['id' => $session->id, 'title' => $session->title ?? 'New conversation'],
            'messages' => $messages,
        ]);
    }

    /**
     * Stream a Gemini response for a specific session via SSE.
     */
    public function stream(Request $request, ChatSession $session): StreamedResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        $request->validate(['message' => 'required|string|max:1000']);

        $message = $request->input('message');

        return response()->stream(function () use ($session, $message) {
            foreach ($this->chat->stream($session, $message) as $chunk) {
                echo 'data: ' . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Delete a session and all its messages.
     */
    public function destroy(Request $request, ChatSession $session)
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        $session->delete();
        return response()->json(['ok' => true]);
    }
}
