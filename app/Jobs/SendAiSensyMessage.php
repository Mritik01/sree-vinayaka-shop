<?php

namespace App\Jobs;

use App\Services\AiSensyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// queueable so sending this never blocks the request/command that triggered it — a no-op change
// while QUEUE_CONNECTION=sync (today's setting runs it inline exactly as before), but takes
// effect for free the moment a real queue driver is configured, no other code changes needed —
// same precedent as App\Notifications\AdminMessage/OrderCancelled. Constructor takes only
// primitive args (not an Order/User model) so this stays trivially serializable if it's ever
// actually queued and delayed.
class SendAiSensyMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventKey,
        public string $phone,
        public array $templateParams = [],
        public ?string $recipientName = null,
    ) {
    }

    public function handle(AiSensyService $aiSensy): void
    {
        $aiSensy->send($this->eventKey, $this->phone, $this->templateParams, $this->recipientName);
    }
}
