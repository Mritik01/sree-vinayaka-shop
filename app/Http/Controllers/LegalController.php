<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use Illuminate\Support\Str;

class LegalController extends Controller
{
    public function terms()
    {
        return $this->show('terms');
    }

    public function privacy()
    {
        return $this->show('privacy');
    }

    public function refund()
    {
        return $this->show('refund');
    }

    public function shipping()
    {
        return $this->show('shipping');
    }

    private function show(string $type)
    {
        $document = LegalDocumentVersion::current($type);

        abort_unless($document, 404);

        return view('legal-document', [
            'title' => $document->title,
            'content' => $document->content,
            'updatedAt' => $document->published_at->format('d M Y'),
            // strip_tags() only removes markup, not entities — decode &amp; etc. back to plain
            // text first, or the meta description reads literally as "Terms &amp; Conditions".
            // Quill leaves blank <p></p> paragraphs between sections, so collapse the resulting
            // run of blank lines/whitespace down to single spaces for a clean one-line summary.
            'metaDescription' => Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($document->content)))), 155),
        ]);
    }
}
