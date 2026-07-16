<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'headline',
        'description',
        'button_text',
        'button_url',
        'image_path',
        'theme',
        'background_color',
        'text_color',
        'display_frequency',
        'show_close_button',
        'auto_close_seconds',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'show_close_button' => 'boolean',
        'auto_close_seconds' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    // a banner with nothing to say has no business popping up, even if left toggled on
    public function isLive(): bool
    {
        return $this->is_enabled && (filled($this->headline) || filled($this->description));
    }
}
