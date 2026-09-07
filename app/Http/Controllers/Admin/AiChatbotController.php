<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\Admin\AiChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiChatbotController extends Controller
{
    public function __construct(protected AiChatbotService $chatbot) {}

    /**
     * Handle a chat message from the tenant.
     * POST /admin/ai-chatbot/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:600',
            'language'        => 'nullable|string|in:en,hi,gu,hinglish',
            'conversation_id' => 'nullable|integer',
        ]);

        $user     = $request->user();
        $language = $validated['language'] ?? 'en';
        $message  = trim($validated['message']);

        // ── Gate 1: per-tenant DAILY TOKEN budget (strict cost cap) ────────
        if (! check_plan_limit('ai_tokens')) {
            return response()->json([
                'success' => false,
                'reply'   => "You've reached today's AI usage limit. It resets tomorrow — or upgrade your plan for more.",
            ], 429);
        }

        // ── Gate 2: per-tenant daily MESSAGE count (coarse cap) ────────────
        if (! check_plan_limit('ai_chats')) {
            return response()->json([
                'success' => false,
                'reply'   => "You've reached your daily AI message limit. It resets tomorrow — or upgrade your plan for more.",
            ], 429);
        }

        // ── Resolve the conversation thread (per-user, tenant-scoped) ──────
        $conversation = null;
        if (! empty($validated['conversation_id'])) {
            $conversation = AiConversation::forUser($user->id)
                ->find($validated['conversation_id']);
        }
        if (! $conversation) {
            $conversation = AiConversation::create([
                'user_id'         => $user->id,
                'title'           => Str::limit($message, 60, '…'),
                'last_message_at' => now(),
            ]);
        }

        // ── Short memory window: last 2 turns (≤4 messages), trimmed ───────
        $history = $conversation->messages()
            ->reorder()
            ->latest('id')
            ->take(4)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn ($m) => [
                'role'    => $m->role,
                'content' => Str::limit($m->content, 240, ''),
            ])
            ->values()
            ->all();

        // ── Run the assistant ──────────────────────────────────────────────
        $result = $this->chatbot->chat(
            message:  $message,
            language: $language,
            history:  $history,
        );

        // ── Persist the turn ────────────────────────────────────────────────
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => AiMessage::ROLE_USER,
            'content'         => $message,
            'tokens'          => 0,
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => AiMessage::ROLE_ASSISTANT,
            'content'         => $result['reply'] ?? '',
            'tokens'          => (int) ($result['tokens'] ?? 0),
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        // ── Record usage only for answered turns ───────────────────────────
        if ($result['success']) {
            record_ai_usage((int) ($result['tokens'] ?? 0));
        }

        return response()->json([
            'success'         => $result['success'],
            'reply'           => $result['reply'] ?? '',
            'conversation_id' => $conversation->id,
        ], $result['success'] ? 200 : 422);
    }
    /**
     * Return today's AI usage for the current tenant.
     * GET /admin/ai-chatbot/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $plan = tenant_subscription()?->plan;

        $msgLimit   = (int) ($plan?->ai_chat_daily_limit ?? 0);
        $tokenLimit = (int) ($plan?->ai_token_daily_limit ?? 0);

        $msgUsed   = (int) Cache::get(ai_chat_usage_key(), 0);
        $tokenUsed = (int) Cache::get(ai_token_usage_key(), 0);

        return response()->json([
            'tokens' => [
                'used'      => $tokenUsed,
                'limit'     => $tokenLimit,   // -1 = unlimited
                'remaining' => $tokenLimit === -1 ? null : max(0, $tokenLimit - $tokenUsed),
                'unlimited' => $tokenLimit === -1,
            ],
            'messages' => [
                'used'      => $msgUsed,
                'limit'     => $msgLimit,     // -1 = unlimited
                'remaining' => $msgLimit === -1 ? null : max(0, $msgLimit - $msgUsed),
                'unlimited' => $msgLimit === -1,
            ],
        ]);
    }

    /**
     * Return predefined quick questions for the UI buttons.
     * GET /admin/ai-chatbot/quick-questions
     */
    public function quickQuestions(): JsonResponse
    {
        return response()->json([
            'success'   => true,
            'questions' => AiChatbotService::quickQuestions(),
        ]);
    }

    /**
     * List the current user's chat threads, newest first.
     */
    public function conversations(Request $request): JsonResponse
    {
        $conversations = AiConversation::forUser($request->user()->id)
            ->orderByDesc('last_message_at')
            ->take(50)
            ->get(['id', 'title', 'last_message_at']);

        return response()->json([
            'success'       => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Return all messages for one thread (history view).
     */
    public function conversation(Request $request, int $conversation): JsonResponse
    {
        $thread = AiConversation::forUser($request->user()->id)->find($conversation);

        if (! $thread) {
            return response()->json(['success' => false, 'reply' => 'Conversation not found.'], 404);
        }

        return response()->json([
            'success'         => true,
            'conversation_id' => $thread->id,
            'messages'        => $thread->messages()->get(['role', 'content']),
        ]);
    }

    /**
     * Delete a thread (messages cascade-delete).
     */
    public function destroyConversation(Request $request, int $conversation): JsonResponse
    {
        $thread = AiConversation::forUser($request->user()->id)->find($conversation);

        if ($thread) {
            $thread->delete();
        }

        return response()->json(['success' => true]);
    }
}