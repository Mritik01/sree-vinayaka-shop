<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementSetting;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function edit()
    {
        $announcement = AnnouncementSetting::current();

        return view('admin.announcement.edit', [
            'announcement' => $announcement,
            // already ordered by announcement_products.sort_order (AnnouncementSetting::products())
            // — kept even while a different mode is active, so switching back to "Custom Selected
            // Products" later restores the last picks instead of starting from an empty list
            'selectedProducts' => $announcement->products,
            'discountedCount' => \App\Models\Product::whereNotNull('discount_type')->where('discount_value', '>', 0)->count(),
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
            'landing_page_mode' => 'required|in:'.implode(',', AnnouncementSetting::LANDING_PAGE_MODES),
            // order in the array IS the display order — see the sync() call below. Not required:
            // an admin can pick "Custom Selected Products" and simply not have added any yet,
            // same "let it be empty rather than block the save" latitude coupons/tags etc get.
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ], [
            'button_url.regex' => 'The button link must start with http://, https://, or /.',
        ]);

        $announcement = AnnouncementSetting::current();

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['show_close_button'] = $request->boolean('show_close_button');
        $data['description'] = $this->sanitizeDescription($data['description'] ?? null);
        $productIds = $data['product_ids'] ?? [];
        unset($data['image'], $data['remove_image'], $data['product_ids']);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                @unlink(public_path($announcement->image_path));
            }
            $file = $request->file('image');
            $original = file_get_contents($file->getRealPath());
            // uploads at/above 400KB get re-encoded down toward 150KB — see ImageCompressor
            $compressed = ImageCompressor::compressToJpeg($original);
            $wasCompressed = $compressed !== $original;

            $extension = $wasCompressed ? 'jpg' : ($file->extension() ?: 'jpg');
            $filename = 'announcement-'.Str::random(8).'.'.$extension;
            $directory = public_path('images/announcements');

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($directory.'/'.$filename, $compressed);
            $data['image_path'] = 'images/announcements/'.$filename;
        } elseif ($request->boolean('remove_image')) {
            if ($announcement->image_path) {
                @unlink(public_path($announcement->image_path));
            }
            $data['image_path'] = null;
        }

        $announcement->update($data);

        // sync() diffs against the existing pivot rows itself (no manual detach/attach needed);
        // array order here is what the admin's drag-reorder picker produced (product_ids[] was
        // submitted in that order), stamped onto sort_order so AnnouncementSetting::products()
        // can play it back in the same order on the actual landing page
        $announcement->products()->sync(
            collect($productIds)->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all()
        );

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
