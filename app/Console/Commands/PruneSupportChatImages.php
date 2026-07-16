<?php

namespace App\Console\Commands;

use App\Models\SupportMessage;
use Illuminate\Console\Command;

class PruneSupportChatImages extends Command
{
    protected $signature = 'support-chat-images:prune';

    protected $description = 'Deletes support-chat photo attachments 2 days after they were sent, keeping the message text (if any)';

    public function handle(): void
    {
        $messages = SupportMessage::whereNotNull('image_path')
            ->where('created_at', '<=', now()->subDays(2))
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No support-chat images old enough to prune.');

            return;
        }

        foreach ($messages as $message) {
            @unlink(public_path($message->image_path));

            $message->forceFill([
                'image_path' => null,
                // an image-only message would otherwise leave a blank bubble in the history
                // once its photo is gone — a captioned message keeps its own text untouched
                'message' => $message->message !== '' ? $message->message : '📷 Photo (removed after 2 days)',
            ])->save();
        }

        $this->info("Pruned {$messages->count()} support-chat image(s) older than 2 days.");
    }
}
