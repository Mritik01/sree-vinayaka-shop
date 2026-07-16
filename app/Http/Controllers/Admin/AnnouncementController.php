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
            'button_url' => 'nullable|string|max:255',
            'theme' => 'required|in:maroon,gold,pista,dark,custom',
            'background_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'display_frequency' => 'required|in:every_visit,once_per_session,once_per_day',
            'auto_close_seconds' => 'nullable|integer|min:3|max:120',
            'image' => 'nullable|image|max:4096',
            'remove_image' => 'sometimes|boolean',
        ]);

        $announcement = AnnouncementSetting::current();

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['show_close_button'] = $request->boolean('show_close_button');
        unset($data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                @unlink(public_path($announcement->image_path));
            }
            $file = $request->file('image');
            $filename = 'announcement-'.Str::random(8).'.'.$file->getClientOriginalExtension();
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
}
