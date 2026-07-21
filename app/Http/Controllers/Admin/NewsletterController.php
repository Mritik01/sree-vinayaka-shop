<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    use PaginatesAdminLists;

    // read-only list of emails captured by the homepage "Join the Shree Vinayak Parivaar" form
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::latest();

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where('email', 'like', "%{$search}%");
        }

        $data = [
            'subscribers' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
            'totalCount' => NewsletterSubscriber::count(),
        ];

        return $request->ajax() ? view('admin.newsletter._results', $data) : view('admin.newsletter.index', $data);
    }
}
