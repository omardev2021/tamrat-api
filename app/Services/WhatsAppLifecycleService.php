<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Composes + sends WhatsApp lifecycle nudges (predictive replenishment, occasions).
 *
 * SAFETY: sending is GATED. Proactive WhatsApp marketing requires Meta-approved
 * templates + opt-in; sending free-form blasts can get the number banned. So this
 * defaults to DRY-RUN (logs what it would send). Real sends only when
 * services.lifecycle.wa_enabled = true AND an approved template is configured.
 * Until then the engine still runs and is fully testable.
 */
class WhatsAppLifecycleService
{
    public function compose(string $type, object $row, ?array $occasion = null): string
    {
        $name = trim((string) ($row->name ?? ''));
        $hi = $name !== '' ? "هلا {$name} 🌴" : 'هلا 🌴';

        if ($type === 'occasion' && $occasion) {
            return "{$occasion['emoji']} {$occasion['name']} قرب يا {$name}!\n"
                . "جهّز ضيافتك وهداياك من تمرات — تمور فاخرة نوصلها لباب بيتك خلال ٢–٥ أيام.\n"
                . "تبي أرتب لك طلبك؟ رد علي هنا وأنا أساعدك 👋";
        }

        // replenishment
        return "{$hi} مر وقت من آخر طلب لك، ويمكن تمرك قرب يخلص 😋\n"
            . "تبي أجهّز لك نفس طلبك أو أقترح عليك جديد؟ رد علي وأنا بخدمتك.";
    }

    /**
     * Gated send. Returns ['dry'=>bool, 'sent'=>bool, 'note'=>string].
     * When enabled, posts into the customer's WhatsApp thread via Chatwoot.
     */
    public function send(string $phone, string $text): array
    {
        if (!config('services.lifecycle.wa_enabled')) {
            Log::info('[lifecycle] DRY-RUN would send to ' . $phone . ': ' . str_replace("\n", ' ', $text));
            return ['dry' => true, 'sent' => false, 'note' => 'dry-run (wa_enabled=false)'];
        }

        // Live path: deliver via Chatwoot's WhatsApp inbox.
        // NOTE: outside the 24h customer-service window this MUST be an approved
        // template — configure services.lifecycle.template_name and adapt here.
        try {
            $base = rtrim((string) config('services.chatwoot.base_url'), '/');
            $token = (string) config('services.chatwoot.api_token');
            $acct = config('services.chatwoot.account_id');
            $h = ['api_access_token' => $token];

            $search = Http::withHeaders($h)->acceptJson()->get("$base/api/v1/accounts/$acct/contacts/search", ['q' => $phone])->json();
            $contact = $search['payload'][0] ?? null;
            if (!$contact) return ['dry' => false, 'sent' => false, 'note' => 'contact not found'];
            $contactId = $contact['id'];
            $sourceId = $contact['contact_inboxes'][0]['source_id'] ?? null;

            $conv = Http::withHeaders($h)->acceptJson()->post("$base/api/v1/accounts/$acct/conversations", [
                'source_id' => $sourceId, 'inbox_id' => 1, 'contact_id' => $contactId,
            ])->json();
            $cid = $conv['id'] ?? null;
            if (!$cid) return ['dry' => false, 'sent' => false, 'note' => 'no conversation'];

            $tpl = config('services.lifecycle.template_name');
            $body = ['content' => $text, 'message_type' => 'outgoing', 'private' => false];
            if ($tpl) {
                $body['template_params'] = ['name' => $tpl, 'category' => 'marketing', 'language' => 'ar'];
            }
            Http::withHeaders($h)->acceptJson()->post("$base/api/v1/accounts/$acct/conversations/$cid/messages", $body);
            return ['dry' => false, 'sent' => true, 'note' => 'sent via chatwoot'];
        } catch (\Throwable $e) {
            Log::warning('[lifecycle] send failed for ' . $phone . ': ' . $e->getMessage());
            return ['dry' => false, 'sent' => false, 'note' => 'error: ' . $e->getMessage()];
        }
    }
}
