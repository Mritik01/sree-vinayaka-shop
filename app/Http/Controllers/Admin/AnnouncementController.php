<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function edit()
    {
        return view('admin.announcement.edit', [
            'announcement' => AnnouncementSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'headline' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:4000',
            'button_text' => 'nullable|string|max:40',
            // this banner is shown to every site visitor, and the value is bound straight to an
            // <a :href> on the front end (see partials/announcement-banner-content.blade.php) —
            // without a scheme allowlist, a "javascript:" URL here would run in every visitor's
            // browser the moment they click the button
            'button_url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|#)/i'],
            'theme' => 'required|in:maroon,gold,pista,dark,custom',
            'background_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'display_frequency' => 'required|in:every_visit,once_per_session,once_per_day',
            'auto_close_seconds' => 'nullable|integer|min:3|max:120',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            'remove_image' => 'sometimes|boolean',
        ], [
            'button_url.regex' => 'The button link must start with http://, https://, or /.',
        ]);

        $announcement = AnnouncementSetting::current();

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['show_close_button'] = $request->boolean('show_close_button');
        $data['description'] = $this->sanitizeDescription($data['description'] ?? null);
        unset($data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                @unlink(public_path($announcement->image_path));
            }
            $file = $request->file('image');
            $filename = 'announcement-'.Str::random(8).'.'.($file->extension() ?: 'jpg');
            $file->move(public_path('images/announcements'), $filename);
            $data['image_path'] = 'images/announcements/'.$filename;
        } elseif ($request->boolean('remove_image')) {
            if ($announcement->image_path) {
                @unlink(public_path($announcement->image_path));
            }
            $data['image_path'] = null;
        }

        $announcement->update($data);

        return redirect()->route('admin.announcement.edit')->with('status', 'Announcement banner updated.');
    }

    // the description comes from the Quill rich-text editor (admin/announcement/edit.blade.php)
    // and is rendered with x-html to every site visitor (see partials/announcement-banner-content),
    // so it must never reach the database with anything beyond the small set of formatting tags
    // Quill's own toolbar can actually produce — this stops a compromised/malicious admin account
    // from turning the banner into a site-wide stored-XSS payload against every customer
    private function sanitizeDescription(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,a[href],span[style],ul,ol,li');
        $config->set('CSS.AllowedProperties', 'color,text-align,text-decoration,font-weight,font-style');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));

        return (new \HTMLPurifier($config))->purify($html);
    }
}
