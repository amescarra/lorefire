<?php

namespace App\Http\Controllers;

use App\Jobs\AskOracle;
use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\OracleReply;
use App\Support\Adnd2eOracleBriefing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OracleController extends Controller
{
    public function index(): Response
    {
        $campaigns = Campaign::with(['characters', 'gameSessions' => fn ($q) => $q->latest()->limit(5)])->get();
        $provider  = AppSetting::get('llm_provider', 'none');

        return Inertia::render('Oracle/Index', [
            'campaigns' => $campaigns,
            'hasLlm'    => $provider !== 'none',
        ]);
    }

    /**
     * Dispatch the Oracle job and return the reply ID for polling.
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'messages'           => 'required|array|min:1',
            'messages.*.role'    => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:8000',
            'context'            => 'nullable|array',
        ]);

        $provider = AppSetting::get('llm_provider', 'none');

        if ($provider === 'none') {
            return response()->json(['error' => 'No LLM provider configured. Set one in Settings.'], 422);
        }

        $systemPrompt = $this->buildSystemPrompt($request->input('context', []));
        $messages     = $request->input('messages');

        $reply = OracleReply::create(['status' => 'pending']);

        AskOracle::dispatch($reply, $systemPrompt, $messages);

        return response()->json(['reply_id' => $reply->id]);
    }

    /**
     * Poll for a reply's status and result.
     */
    public function replyStatus(OracleReply $reply): JsonResponse
    {
        return response()->json([
            'status' => $reply->status,
            'reply'  => $reply->reply,
        ]);
    }

    // ── System prompt ──────────────────────────────────────────────────────

    public function buildSystemPrompt(array $context): string
    {
        return Adnd2eOracleBriefing::systemPrompt($context);
    }
}
