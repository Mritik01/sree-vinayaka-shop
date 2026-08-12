<?php

namespace App\Services;

use App\Models\ShopSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSensyService
{
    // maps each event key to the ShopSetting column that gates it — see send()
    protected const EVENT_TOGGLE_COLUMNS = [
        'order_confirmed' => 'aisensy_notify_order_confirmed',
        'out_for_delivery' => 'aisensy_notify_out_for_delivery',
        'delivered' => 'aisensy_notify_delivered',
        'cancelled' => 'aisensy_notify_cancelled',
        'abandoned_cart' => 'aisensy_notify_abandoned_cart',
    ];

    protected string $apiKey;
    protected string $baseUrl;
    protected array $campaigns;
    protected array $media;

    public function __construct()
    {
        $this->apiKey = (string) config('services.aisensy.api_key');
        $this->baseUrl = (string) config('services.aisensy.base_url');
        $this->campaigns = (array) config('services.aisensy.campaigns');
        $this->media = (array) config('services.aisensy.media');
    }

    /**
     * Send a templated WhatsApp message via AiSensy for the given event
     * (one of the keys in self::EVENT_TOGGLE_COLUMNS). Never throws — every
     * failure path logs and returns false, since this is always a side effect
     * of an order/cart event and must never break the flow that triggered it.
     *
     * $phone must already be a bare 10-digit Indian mobile number (see
     * App\Support\PhoneNumber::normalize()) — this method adds the 91 prefix.
     * $templateParams is a positional list matching the approved template's
     * {{1}}, {{2}}... placeholders, in order.
     */
    public function send(string $eventKey, string $phone, array $templateParams = [], ?string $recipientName = null): bool
    {
        $settings = ShopSetting::current();

        if (!$settings->aisensy_enabled) {
            return false;
        }

        $toggleColumn = self::EVENT_TOGGLE_COLUMNS[$eventKey] ?? null;
        if ($toggleColumn === null || !$settings->{$toggleColumn}) {
            return false;
        }

        $campaignName = $this->campaigns[$eventKey] ?? null;

        if ($this->apiKey === '' || !$campaignName) {
            Log::warning("AiSensy send skipped: missing API key or campaign name for event '{$eventKey}'.");

            return false;
        }

        $payload = [
            'apiKey' => $this->apiKey,
            'campaignName' => $campaignName,
            'destination' => '91'.$phone,
            'userName' => $recipientName ?? $phone,
            'templateParams' => array_values($templateParams),
        ];

        // only templates with an image/document header need this — AiSensy rejects the request
        // with "Media URL Missing" if the template has one and this is omitted, so it's only
        // attached when a URL is actually configured for this event (see AISENSY_MEDIA_* in .env)
        $mediaUrl = $this->media[$eventKey] ?? null;
        if ($mediaUrl) {
            $payload['media'] = [
                'url' => $mediaUrl,
                'filename' => basename(parse_url($mediaUrl, PHP_URL_PATH) ?: 'image.jpg'),
            ];
        }

        try {
            $response = Http::timeout(5)->post($this->baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('AiSensy send failed', [
                    'event' => $eventKey,
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('AiSensy send threw', [
                'event' => $eventKey,
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
