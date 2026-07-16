<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $methodFilter = in_array($request->get('method'), ['cod', 'razorpay'], true) ? $request->get('method') : '';
        $statusFilter = in_array($request->get('payment_status'), ['pending', 'paid', 'failed'], true) ? $request->get('payment_status') : '';
        $search = trim((string) $request->get('q', ''));

        $query = Order::with('user:id,name,phone')->latest();

        if ($methodFilter !== '') {
            $query->where('payment_method', $methodFilter);
        }
        if ($statusFilter !== '') {
            $query->where('payment_status', $statusFilter);
        }
        if ($search !== '') {
            // "MKB-2026-000047" order number or "TXID00000047" transaction id both resolve
            // straight to the order they stand for
            $orderNumberId = Order::idFromOrderNumber($search);
            $transactionOrderId = Order::idFromTransactionId($search);

            $query->where(function ($q) use ($search, $orderNumberId, $transactionOrderId) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$search}%")
                    ->orWhere('razorpay_payment_id', 'like', "%{$search}%");

                if ($orderNumberId !== null) {
                    $q->orWhere('id', $orderNumberId);
                }
                if ($transactionOrderId !== null) {
                    $q->orWhere('id', $transactionOrderId);
                }
            });
        }

        $transactions = $query->paginate($this->perPage($request))->withQueryString();

        // an AJAX live-search request only ever needs the results partial
        if ($request->ajax()) {
            return view('admin.transactions._results', ['transactions' => $transactions, 'search' => $search]);
        }

        // summary cards always reflect ALL transactions, never the current search/filter/page
        $summary = [
            'total_count' => Order::count(),
            'cod_amount' => (int) Order::where('payment_method', 'cod')->where('status', '!=', 'cancelled')->sum('total'),
            'online_paid_amount' => (int) Order::where('payment_method', 'razorpay')->where('payment_status', 'paid')->sum('total'),
            'failed_count' => Order::where('payment_status', 'failed')->count(),
        ];

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'search' => $search,
            'methodFilter' => $methodFilter,
            'statusFilter' => $statusFilter,
            'summary' => $summary,
        ]);
    }
}
