<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Platform Income Report</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #3a1212; font-size: 12px; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 3px solid #b8860b; padding-bottom: 14px; margin-bottom: 20px; }
        .shop-name { font-size: 22px; font-weight: bold; color: #5c0f1e; margin: 0; }
        .shop-meta { font-size: 11px; color: #7a5a5a; margin-top: 4px; line-height: 1.5; }
        .report-title { font-size: 18px; font-weight: bold; color: #5c0f1e; text-align: right; margin: 0; }
        .report-meta { font-size: 11px; color: #7a5a5a; text-align: right; margin-top: 4px; line-height: 1.6; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        table.summary td { padding: 10px; border: 1px solid #eee0cc; font-size: 12px; text-align: center; }
        table.summary td .label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; color: #b8860b; font-weight: bold; margin-bottom: 4px; }
        table.summary td .value { display: block; font-size: 16px; font-weight: bold; color: #5c0f1e; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th { background-color: #5c0f1e; color: #fff; text-align: left; padding: 7px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
        table.items thead th.num { text-align: right; }
        table.items tbody td { padding: 6px 8px; border-bottom: 1px solid #eee0cc; font-size: 10.5px; }
        table.items tbody td.num { text-align: right; }
        .footer-note { margin-top: 30px; padding-top: 12px; border-top: 1px solid #eee0cc; text-align: center; font-size: 10px; color: #7a5a5a; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <p class="shop-name">Shree Vinayak Family Shop</p>
                <p class="shop-meta">Platform Income Report — Super Admin Only</p>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <p class="report-title">INCOME REPORT</p>
                <p class="report-meta">Generated {{ now()->format('d M Y, h:i A') }}</p>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="label">Orders</span><span class="value">{{ number_format($summary['orders']) }}</span></td>
            <td><span class="label">₹15 Commission Total</span><span class="value">₹{{ number_format($summary['fixed_total']) }}</span></td>
            <td><span class="label">Delivery Charge Income</span><span class="value">₹{{ number_format($summary['delivery_total']) }}</span></td>
            <td><span class="label">Grand Total</span><span class="value">₹{{ number_format($summary['grand_total']) }}</span></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Delivery Partner</th>
                <th class="num">Order Amt</th>
                <th class="num">Delivery Chg</th>
                <th class="num">₹15 Income</th>
                <th class="num">Delivery Income</th>
                <th class="num">Total Income</th>
                <th>Delivered At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->order ? $record->order->orderNumber() : '#'.$record->order_id }}</td>
                    <td>{{ $record->customer_name }}</td>
                    <td>{{ $record->rider->name ?? '—' }}</td>
                    <td class="num">₹{{ number_format($record->order_amount) }}</td>
                    <td class="num">₹{{ number_format($record->delivery_charge) }}</td>
                    <td class="num">₹{{ number_format($record->fixed_commission) }}</td>
                    <td class="num">₹{{ number_format($record->delivery_charge_income) }}</td>
                    <td class="num">₹{{ number_format($record->total_income) }}</td>
                    <td>{{ $record->delivered_at->format('d M Y, h:i A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer-note">Shree Vinayak Family Shop · Internal financial document · Confidential</p>
</body>
</html>
