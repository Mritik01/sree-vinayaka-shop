<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalDocumentController extends Controller
{
    private const TITLES = [
        'terms' => 'Terms & Conditions',
        'privacy' => 'Privacy Policy',
    ];

    public function index()
    {
        $documents = collect(LegalDocumentVersion::TYPES)->map(fn ($type) => [
            'type' => $type,
            'label' => self::TITLES[$type],
            'current' => LegalDocumentVersion::current($type),
        ]);

        return view('admin.legal.index', ['documents' => $documents]);
    }

    public function edit(string $type)
    {
        abort_unless(in_array($type, LegalDocumentVersion::TYPES, true), 404);

        return view('admin.legal.edit', [
            'type' => $type,
            'label' => self::TITLES[$type],
            'current' => LegalDocumentVersion::current($type),
            'history' => LegalDocumentVersion::where('type', $type)->with('admin')->orderByDesc('version')->get(),
        ]);
    }

    public function update(Request $request, string $type)
    {
        abort_unless(in_array($type, LegalDocumentVersion::TYPES, true), 404);

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string',
        ]);

        $nextVersion = (int) (LegalDocumentVersion::where('type', $type)->max('version') ?? 0) + 1;

        LegalDocumentVersion::create([
            'type' => $type,
            'version' => $nextVersion,
            'title' => $data['title'],
            'content' => $this->sanitize($data['content']),
            'published_at' => now(),
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.legal.edit', $type)
            ->with('status', self::TITLES[$type]." updated — now version {$nextVersion}. Customers who already agreed will be prompted to accept the new version.");
    }

    // legal documents need real document structure (headings, lists, quotes) — a wider
    // allowlist than the announcement banner's, but still stripped of anything script-capable
    // before it's ever rendered raw on the public pages, the modal, or the live preview (see
    // Admin\AnnouncementController::sanitizeDescription for the same pattern, narrower scope)
    private function sanitize(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'h2,h3,h4,p,br,b,strong,i,em,u,a[href],ul,ol,li,blockquote');
        $config->set('CSS.AllowedProperties', '');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));

        return (new \HTMLPurifier($config))->purify($html);
    }
}
