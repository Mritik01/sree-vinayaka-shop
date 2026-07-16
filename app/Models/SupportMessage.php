<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SupportMessage extends Model
{
    protected $fillable = [
        'sender',
        'message',
        'image_path',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // shared by both the customer and admin send() endpoints — same public-disk convention
    // as Rider\OrderController::uploadPhoto() (random filename, no originals kept)
    public static function storeImage(UploadedFile $file): string
    {
        $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images/support-chat'), $filename);

        return 'images/support-chat/'.$filename;
    }

    // one consistent shape for every chat endpoint (customer fetch/send, admin fetch/send) —
    // the client groups bubbles into day sections by comparing consecutive `day` values
    public function forJs(): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'message' => $this->message,
            'image_url' => $this->image_path ? asset($this->image_path) : null,
            'time' => $this->created_at->format('h:i A'),
            'day' => $this->created_at->format('d M Y'),
        ];
    }
}
