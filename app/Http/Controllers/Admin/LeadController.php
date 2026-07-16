<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    use PaginatesAdminLists;

    // read-only list of responses captured by the "Shadi ho ya Function?" popup
    public function index(Request $request)
    {
        $query = Lead::latest('verified_at');

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $data = [
            'leads' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
            'totalCount' => Lead::count(),
        ];

        return $request->ajax() ? view('admin.leads._results', $data) : view('admin.leads.index', $data);
    }
}
