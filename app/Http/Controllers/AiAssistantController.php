<?php

namespace App\Http\Controllers;

use App\Exceptions\GeminiException;
use App\Models\AiConversation;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function __construct(private readonly GeminiService $gemini) {}

    public function index(Request $request): View|RedirectResponse
    {
        return view('ai.chat', [
            'conversations' => $this->conversations($request),
            'conversation' => null,
            'configured' => $this->gemini->isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['prompt' => ['nullable', 'string', 'max:4000']]);

        $conversation = $request->user()->aiConversations()->create(['title' => __('New conversation')]);

        if (! empty($data['prompt'])) {
            try {
                $this->gemini->reply($conversation, $data['prompt']);
            } catch (GeminiException $e) {
                return redirect()->route('ai.conversations.show', $conversation)->with('error', $e->getMessage());
            }
        }

        return redirect()->route('ai.conversations.show', $conversation);
    }

    public function show(Request $request, AiConversation $conversation): View
    {
        $this->authorizeConversation($request, $conversation);

        return view('ai.chat', [
            'conversations' => $this->conversations($request),
            'conversation' => $conversation->load('messages'),
            'configured' => $this->gemini->isConfigured(),
        ]);
    }

    public function message(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        try {
            $answer = $this->gemini->reply($conversation, $data['message']);
        } catch (GeminiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $messages = $conversation->messages()->reorder()->latest('id')->take(2)->get()->reverse();

        return response()->json([
            'html' => view('ai._messages', ['messages' => $messages])->render(),
            'title' => $conversation->fresh()->title,
        ]);
    }

    public function destroy(Request $request, AiConversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->delete();

        return redirect()->route('ai.index')->with('success', __('Conversation deleted.'));
    }

    public function insights(Request $request): View
    {
        return view('ai.insights', [
            'insight' => Cache::get($this->insightKey($request)),
            'configured' => $this->gemini->isConfigured(),
        ]);
    }

    public function generateInsights(Request $request): RedirectResponse
    {
        $data = $request->validate(['focus' => ['required', 'in:general,churn,revenue,pricing,sellers']]);

        try {
            $text = $this->gemini->analyzeBusiness($data['focus']);
        } catch (GeminiException $e) {
            return back()->with('error', $e->getMessage());
        }

        Cache::put($this->insightKey($request), [
            'focus' => $data['focus'],
            'text' => $text,
            'generated_at' => now()->toDateTimeString(),
        ], now()->addDays(7));

        return back()->with('success', __('Analysis generated.'));
    }

    protected function conversations(Request $request)
    {
        return $request->user()->aiConversations()->latest('updated_at')->take(30)->get();
    }

    protected function authorizeConversation(Request $request, AiConversation $conversation): void
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);
    }

    protected function insightKey(Request $request): string
    {
        return 'ai_insights_'.$request->user()->id;
    }
}
