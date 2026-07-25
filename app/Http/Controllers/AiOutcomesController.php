<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CommerceEvent;

/**
 * AI outcomes for Tamrat — surfaces the business result of the two AI agents from the
 * CommerceEvent flywheel, so the Watar Dashboard can measure them (not just run them).
 *
 *   WhatsApp commerce agent : conversations, orders, revenue, AOV, conversion rate,
 *                             revenue/conversation, escalations, orders-by-day.
 *   FAQ answer-bot          : questions, handoff (buying-intent) rate, unique topics,
 *                             questions-by-day, top questions.
 *
 * GET /api/ai-outcomes/{secret}?days=30
 */
class AiOutcomesController extends Controller
{
    public function outcomes(Request $request, string $secret)
    {
        $expected = (string) config('services.faq.insights_secret');
        if (!$expected || !hash_equals($expected, $secret)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $days  = min(180, max(1, (int) $request->query('days', 30)));
        $since = now()->subDays($days);

        // ── WhatsApp commerce agent ─────────────────────────────────────────────
        $orders = CommerceEvent::where('type', 'order_created')->where('created_at', '>=', $since);
        $orderCount   = (clone $orders)->count();
        $revenue      = (float) (clone $orders)->sum('price_point');
        // A "conversation" = any commerce event with a conversation_id (FAQ questions have none).
        $conversations = (int) CommerceEvent::where('created_at', '>=', $since)
            ->whereNotNull('conversation_id')
            ->distinct()->count('conversation_id');
        $escalations = (int) CommerceEvent::where('type', 'objection')->where('created_at', '>=', $since)->count();

        $ordersByDay = CommerceEvent::where('type', 'order_created')->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(price_point),0) as rev')
            ->groupBy('d')->orderBy('d')->get()
            ->map(fn ($r) => ['date' => $r->d, 'count' => (int) $r->c, 'revenue' => (float) $r->rev]);

        // ── FAQ answer-bot ──────────────────────────────────────────────────────
        $faq = CommerceEvent::where('type', 'faq_question')->where('created_at', '>=', $since);
        $questionCount = (clone $faq)->count();
        $handoffs      = (int) (clone $faq)->where('converted', true)->count();

        $questionsByDay = CommerceEvent::where('type', 'faq_question')->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')->get()
            ->map(fn ($r) => ['date' => $r->d, 'count' => (int) $r->c]);

        $topRows = CommerceEvent::where('type', 'faq_question')->where('created_at', '>=', $since)
            ->orderByDesc('created_at')->limit(2000)->get(['query', 'converted']);
        $groups = [];
        foreach ($topRows as $r) {
            $q = trim((string) $r->query);
            if ($q === '') continue;
            $key = trim(preg_replace('/[?؟.!،,]+$/u', '', preg_replace('/\s+/u', ' ', mb_strtolower($q))));
            $groups[$key] ??= ['text' => $q, 'count' => 0, 'handoffs' => 0];
            $groups[$key]['count']++;
            if ($r->converted) $groups[$key]['handoffs']++;
        }
        usort($groups, fn ($a, $b) => $b['count'] <=> $a['count']);

        $round = fn ($n, $p = 0) => $n === null ? null : round($n, $p);

        return response()->json([
            'since' => $since->toDateString(),
            'days'  => $days,
            'whatsapp' => [
                'conversations'          => $conversations,
                'orders'                 => $orderCount,
                'revenue_sar'            => $round($revenue),
                'aov_sar'                => $orderCount ? $round($revenue / $orderCount) : null,
                'conversion_rate'        => $conversations ? $round($orderCount / $conversations, 4) : null,
                'revenue_per_conversation_sar' => $conversations ? $round($revenue / $conversations) : null,
                'escalations'            => $escalations,
                'orders_by_day'          => $ordersByDay,
            ],
            'faq' => [
                'questions'      => $questionCount,
                'handoffs'       => $handoffs,
                'handoff_rate'   => $questionCount ? $round($handoffs / $questionCount, 4) : null,
                'unique_topics'  => count($groups),
                'questions_by_day' => $questionsByDay,
                'top_questions'  => array_slice(array_values($groups), 0, 12),
            ],
        ], 200);
    }
}
