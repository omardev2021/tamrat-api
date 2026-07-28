<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CommerceEvent;
use App\Services\TelegramNotifier;

/**
 * Quality / eval loop for the FAQ answer-bot. Runs daily. Samples recent question→answer pairs
 * and has Claude judge each one (accurate? grounded in real facts? correct handoff? on-brand?),
 * in ONE batched call to keep cost down. Stores a faq_eval per answer and Telegrams a digest —
 * so a bad or hallucinated answer is caught by us, not first by a customer.
 */
class AiQualityReview extends Command
{
    protected $signature = 'ai:quality-review {--hours=24 : look back this many hours} {--limit=25 : max answers to grade} {--test : print the digest instead of sending}';
    protected $description = 'Sample recent FAQ answers and grade them with an LLM judge; alert on flagged answers';

    public function handle(): int
    {
        $hours = min(168, max(1, (int) $this->option('hours')));
        $limit = min(60, max(1, (int) $this->option('limit')));

        $rows = CommerceEvent::where('type', 'faq_question')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'query', 'meta', 'created_at']);

        // Keep only pairs that actually have a logged answer.
        $pairs = [];
        foreach ($rows as $r) {
            $reply = is_array($r->meta) ? trim((string) ($r->meta['reply'] ?? '')) : '';
            $q = trim((string) $r->query);
            if ($q !== '' && $reply !== '') {
                $pairs[] = ['id' => $r->id, 'q' => $q, 'a' => $reply];
            }
        }

        if (empty($pairs)) {
            $this->info('No answered questions in the window — nothing to review.');
            return self::SUCCESS;
        }

        $verdicts = $this->judge($pairs);
        if ($verdicts === null) {
            $this->error('Judge call failed.');
            return self::FAILURE;
        }

        $flagged = [];
        foreach ($pairs as $i => $p) {
            $v = $verdicts[$i] ?? ['score' => null, 'flag' => false, 'reason' => 'no verdict'];
            CommerceEvent::record([
                'type'      => 'faq_eval',
                'query'     => mb_substr($p['q'], 0, 500),
                'converted' => (bool) ($v['flag'] ?? false),
                'meta'      => [
                    'score'  => $v['score'] ?? null,
                    'flag'   => (bool) ($v['flag'] ?? false),
                    'reason' => mb_substr((string) ($v['reason'] ?? ''), 0, 300),
                    'answer' => mb_substr($p['a'], 0, 400),
                ],
            ]);
            if (!empty($v['flag'])) $flagged[] = ['q' => $p['q'], 'a' => $p['a'], 'reason' => $v['reason'] ?? ''];
        }

        $reviewed = count($pairs);
        $nFlag = count($flagged);
        $scores = array_filter(array_map(fn ($v) => $v['score'] ?? null, $verdicts), fn ($s) => $s !== null);
        $avg = $scores ? round(array_sum($scores) / count($scores), 1) : null;

        $digest = "🧪 <b>FAQ bot quality review</b> (last {$hours}h)\n"
            . "Reviewed <b>{$reviewed}</b> answers · avg score " . ($avg ?? '—') . "/5 · flagged <b>{$nFlag}</b>";
        if ($nFlag > 0) {
            $digest .= "\n\n⚠️ <b>Flagged answers:</b>";
            foreach (array_slice($flagged, 0, 8) as $f) {
                $digest .= "\n\n<b>Q:</b> " . e(mb_substr($f['q'], 0, 140))
                    . "\n<b>A:</b> " . e(mb_substr($f['a'], 0, 160))
                    . "\n<b>Why:</b> " . e(mb_substr((string) $f['reason'], 0, 200));
            }
        } else {
            $digest .= "\n✅ No problems found.";
        }

        if ($this->option('test')) {
            $this->line(strip_tags($digest));
        } else {
            // Alert only when there's something to act on; otherwise stay quiet (log the pass).
            if ($nFlag > 0) TelegramNotifier::send($digest);
            $this->info("Reviewed {$reviewed}, flagged {$nFlag}. " . ($nFlag ? 'Digest sent.' : 'No alert (all clean).'));
        }
        return self::SUCCESS;
    }

    /** One batched judge call. Returns array indexed like $pairs, or null on failure. */
    private function judge(array $pairs): ?array
    {
        $key = (string) config('services.anthropic.key');
        if (!$key) return null;

        $list = '';
        foreach ($pairs as $i => $p) {
            $list .= "\n[{$i}]\nQ: {$p['q']}\nA: {$p['a']}\n";
        }

        $system = <<<SYS
You are a strict QA reviewer for Tamrat's on-site FAQ answer-bot (a premium Saudi dates store, tamratdates.com). You are given question→answer pairs the bot gave real visitors. Grade each answer.

The bot's rules (an answer that breaks these should be flagged):
- Only state facts it can know: varieties, prices/stock (from the live catalog), shipping (25 SAR, free over 250, KSA only, 2–5 days), payment (Mada/Visa/Mastercard/STC Pay). It must NEVER invent prices, products, policies, or delivery promises.
- It must NOT take orders, look up a specific order, or handle refunds/complaints itself — those go to WhatsApp. Pushing the visitor to WhatsApp for those is CORRECT, not a problem.
- Tone: warm, concise, natural Saudi Arabic (or English if asked in English). Plain text.

For each pair return: score 1–5 (5 = accurate, grounded, on-brand; 1 = wrong/hallucinated/off-policy), flag = true if the answer is factually wrong, invents something, mishandles a handoff case, or is clearly off-brand. Keep "reason" to one short sentence (Arabic or English).

Return ONLY a JSON array, one object per pair in order, no prose:
[{"i":0,"score":5,"flag":false,"reason":"..."}, ...]
SYS;

        try {
            $res = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => (string) config('services.anthropic.model', 'claude-sonnet-4-6'),
                'max_tokens' => 1500,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => "Grade these pairs:\n{$list}"]],
            ]);
            if (!$res->successful()) {
                Log::warning('[AiQualityReview] judge HTTP ' . $res->status());
                return null;
            }
            $text = '';
            foreach (($res->json('content') ?? []) as $b) {
                if (($b['type'] ?? '') === 'text') $text .= $b['text'];
            }
            $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
            $arr = json_decode($text, true);
            if (!is_array($arr)) return null;
            // Re-index by the "i" field if present, else positional.
            $out = [];
            foreach ($arr as $pos => $v) {
                $idx = isset($v['i']) ? (int) $v['i'] : $pos;
                $out[$idx] = $v;
            }
            return $out;
        } catch (\Throwable $e) {
            Log::error('[AiQualityReview] judge exception: ' . $e->getMessage());
            return null;
        }
    }
}
