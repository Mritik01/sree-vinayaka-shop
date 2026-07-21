<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\IncomeMonthReset;
use App\Models\Order;
use App\Models\PlatformIncomeRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

// Super Admin only — see EnsureSuperAdmin and the admin.super-gated route group. Every figure
// here is a live aggregation over the permanent platform_income_records ledger (written once,
// idempotently, by PlatformIncomeService the moment an order first reaches 'delivered') — there
// is no separate "monthly total" row anywhere to keep in sync, so nothing can ever drift.
class IncomeController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $viewingYear = $request->filled('year') ? (int) $request->year : null;
        $viewingMonth = $request->filled('month') ? (int) $request->month : null;
        $isHistoricalView = $viewingYear !== null && $viewingMonth !== null;

        // "This Month's Income" starts counting from the most recent manual reset this month, if
        // any — otherwise from the 1st, same as before. The reset itself never touches a single
        // platform_income_records row (see resetMonth()'s docblock); this is the one place that
        // audit-only timestamp actually changes what a dashboard figure shows, so clicking Reset
        // visibly zeroes this one card instead of silently doing nothing. Total Platform Income
        // and every other all-time card deliberately ignore this and never reset.
        $latestReset = IncomeMonthReset::where('year', now()->year)->where('month', now()->month)
            ->with('admin')->latest('reset_at')->first();
        $monthStart = $latestReset?->reset_at ?? now()->startOfMonth();

        $cards = [
            'today_income' => (int) PlatformIncomeRecord::where('delivered_at', '>=', now()->startOfDay())->sum('total_income'),
            'month_income' => (int) PlatformIncomeRecord::where('delivered_at', '>=', $monthStart)->sum('total_income'),
            'total_income' => (int) PlatformIncomeRecord::sum('total_income'),
            'total_orders' => PlatformIncomeRecord::count(),
            'fixed_commission_total' => (int) PlatformIncomeRecord::sum('fixed_commission'),
            'delivery_income_total' => (int) PlatformIncomeRecord::sum('delivery_charge_income'),
            'delivery_charge_collected_total' => (int) PlatformIncomeRecord::sum('delivery_charge'),
        ];
        $cards['avg_per_order'] = $cards['total_orders'] > 0 ? (int) round($cards['total_income'] / $cards['total_orders']) : 0;

        // arriving here from a row on the Income History page shows THAT month's totals instead
        // of the live today/this-month cards above — same permanent ledger, just a different
        // date window; nothing here is a separate/archived copy of the data
        $viewingTotals = null;
        if ($isHistoricalView) {
            $viewingTotals = PlatformIncomeRecord::whereYear('delivered_at', $viewingYear)->whereMonth('delivered_at', $viewingMonth)
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(fixed_commission),0) as fixed_total, COALESCE(SUM(delivery_charge_income),0) as delivery_total, COALESCE(SUM(total_income),0) as total')
                ->first();
        }

        // last 12 calendar months, for this dashboard's own summary table/charts — the dedicated
        // Income History page is where every month ever recorded remains browsable
        $monthly = PlatformIncomeRecord::selectRaw('YEAR(delivered_at) as year, MONTH(delivered_at) as month, COUNT(*) as orders, SUM(fixed_commission) as fixed_total, SUM(delivery_charge_income) as delivery_total, SUM(total_income) as total')
            ->where('delivered_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderByDesc('year')->orderByDesc('month')
            ->get();
        $monthlyAsc = $monthly->sortBy(fn ($m) => $m->year * 100 + $m->month)->values();

        $dailyRows = PlatformIncomeRecord::selectRaw('DATE(delivered_at) as day, SUM(total_income) as total, COUNT(*) as orders')
            ->where('delivered_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->get()
            ->keyBy('day');
        $dailyWindow = collect(range(29, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->toDateString());

        $chartData = [
            'daily' => [
                'labels' => $dailyWindow->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
                'income' => $dailyWindow->map(fn ($d) => (int) ($dailyRows[$d]->total ?? 0))->values(),
                'orders' => $dailyWindow->map(fn ($d) => (int) ($dailyRows[$d]->orders ?? 0))->values(),
            ],
            'monthly' => [
                'labels' => $monthlyAsc->map(fn ($m) => Carbon::createFromDate((int) $m->year, (int) $m->month, 1)->format('M Y'))->values(),
                'total' => $monthlyAsc->map(fn ($m) => (int) $m->total)->values(),
                'fixed' => $monthlyAsc->map(fn ($m) => (int) $m->fixed_total)->values(),
                'delivery' => $monthlyAsc->map(fn ($m) => (int) $m->delivery_total)->values(),
                'orders' => $monthlyAsc->map(fn ($m) => (int) $m->orders)->values(),
            ],
            'breakdown' => [
                'fixed' => $cards['fixed_commission_total'],
                'delivery' => $cards['delivery_income_total'],
            ],
        ];

        $ordersQuery = PlatformIncomeRecord::with(['order:id,created_at', 'rider:id,name'])->latest('delivered_at');
        $this->applyFilters($ordersQuery, $request);
        $incomeOrders = $ordersQuery->paginate($this->perPage($request))->withQueryString();

        return view('admin.income.index', [
            'cards' => $cards,
            'monthly' => $monthly,
            'chartData' => $chartData,
            'incomeOrders' => $incomeOrders,
            'search' => $request->get('q', ''),
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'latestReset' => $latestReset,
            'isHistoricalView' => $isHistoricalView,
            'viewingYear' => $viewingYear,
            'viewingMonth' => $viewingMonth,
            'viewingTotals' => $viewingTotals,
        ]);
    }

    public function history(Request $request)
    {
        $yearly = PlatformIncomeRecord::selectRaw('YEAR(delivered_at) as year, COUNT(*) as orders, SUM(fixed_commission) as fixed_total, SUM(delivery_charge_income) as delivery_total, SUM(total_income) as total')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();

        $monthlyQuery = PlatformIncomeRecord::selectRaw('YEAR(delivered_at) as year, MONTH(delivered_at) as month, COUNT(*) as orders, SUM(fixed_commission) as fixed_total, SUM(delivery_charge_income) as delivery_total, SUM(total_income) as total')
            ->groupBy('year', 'month')
            ->orderByDesc('year')->orderByDesc('month');

        $yearFilter = $request->filled('year') ? (int) $request->year : null;
        if ($yearFilter) {
            $monthlyQuery->whereYear('delivered_at', $yearFilter);
        }

        return view('admin.income.history', [
            'yearly' => $yearly,
            'monthly' => $monthlyQuery->get(),
            'yearFilter' => $yearFilter,
        ]);
    }

    // audit-only — see IncomeMonthReset and the migration's docblock. Never touches a single
    // platform_income_records row; "starting fresh" is already true by construction the moment
    // the calendar rolls into a new month, since every figure above is a live date-range query
    public function resetMonth(Request $request)
    {
        IncomeMonthReset::create([
            'year' => now()->year,
            'month' => now()->month,
            'admin_id' => Auth::guard('admin')->id(),
            'reset_at' => now(),
        ]);

        return back()->with('status', "This month's tracking has been reset. No historical income data was deleted — every past order's income remains permanently available in Income History.");
    }

    public function exportCsv(Request $request)
    {
        $ordersQuery = PlatformIncomeRecord::with(['order:id,created_at', 'rider:id,name'])->latest('delivered_at');
        $this->applyFilters($ordersQuery, $request);

        return response()->streamDownload(function () use ($ordersQuery) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order ID', 'Customer Name', 'Delivery Partner', 'Order Amount', 'Delivery Charge', 'Fixed Rs.15 Income', 'Delivery Charge Income', 'Total Income', 'Delivered At']);

            $ordersQuery->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $record) {
                    fputcsv($out, [
                        $record->order ? $record->order->orderNumber() : '#'.$record->order_id,
                        $record->customer_name,
                        $record->rider->name ?? '—',
                        $record->order_amount,
                        $record->delivery_charge,
                        $record->fixed_commission,
                        $record->delivery_charge_income,
                        $record->total_income,
                        $record->delivered_at->format('d M Y, h:i A'),
                    ]);
                }
            });

            fclose($out);
        }, 'platform-income-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request)
    {
        $ordersQuery = PlatformIncomeRecord::with(['order:id,created_at', 'rider:id,name'])->latest('delivered_at');
        $this->applyFilters($ordersQuery, $request);
        $records = $ordersQuery->get();

        $summary = [
            'orders' => $records->count(),
            'fixed_total' => (int) $records->sum('fixed_commission'),
            'delivery_total' => (int) $records->sum('delivery_charge_income'),
            'grand_total' => (int) $records->sum('total_income'),
        ];

        $pdf = Pdf::loadView('admin.income.pdf', ['records' => $records, 'summary' => $summary]);

        return $pdf->download('platform-income-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('year') && $request->filled('month')) {
            $query->whereYear('delivered_at', (int) $request->year)->whereMonth('delivered_at', (int) $request->month);
        }

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $orderId = Order::idFromOrderNumber($search) ?? (ctype_digit(ltrim($search, '#')) ? (int) ltrim($search, '#') : null);
            $query->where(function ($q) use ($search, $orderId) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('rider', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                if ($orderId) {
                    $q->orWhere('order_id', $orderId);
                }
            });
        }

        if ($request->filled('from')) {
            $query->where('delivered_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('delivered_at', '<=', Carbon::parse($request->to)->endOfDay());
        }
    }
}
