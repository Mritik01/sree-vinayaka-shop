<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $order->orderNumber() }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #3a1212; font-size: 12px; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 3px solid #b8860b; padding-bottom: 14px; margin-bottom: 20px; }
        .shop-name { font-size: 22px; font-weight: bold; color: #5c0f1e; margin: 0; }
        .shop-meta { font-size: 11px; color: #7a5a5a; margin-top: 4px; line-height: 1.5; }
        .invoice-title { font-size: 20px; font-weight: bold; color: #5c0f1e; text-align: right; margin: 0; }
        .invoice-meta { font-size: 11px; color: #7a5a5a; text-align: right; margin-top: 4px; line-height: 1.6; }
        .section-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #b8860b; font-weight: bold; margin-bottom: 4px; }
        .bill-to { font-size: 12px; line-height: 1.6; margin-bottom: 22px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items thead th { background-color: #5c0f1e; color: #fff; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
        table.items thead th.num { text-align: right; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #eee0cc; font-size: 12px; }
        table.items tbody td.num { text-align: right; }
        table.items tbody tr.gift td { color: #b8860b; font-style: italic; }
        table.summary { width: 260px; margin-left: auto; border-collapse: collapse; }
        table.summary td { padding: 5px 0; font-size: 12px; }
        table.summary td.label { color: #7a5a5a; }
        table.summary td.value { text-align: right; }
        table.summary tr.total td { border-top: 2px solid #5c0f1e; padding-top: 8px; font-size: 15px; font-weight: bold; color: #5c0f1e; }
        .footer-note { margin-top: 40px; padding-top: 14px; border-top: 1px solid #eee0cc; text-align: center; font-size: 11px; color: #7a5a5a; }
        .status-pill { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; background-color: #f2e6c9; color: #8a6d1d; }
    </style>
</head>
<body>
    @php
        // dompdf has enable_remote=false (config/dompdf.php) so it can't fetch asset() URLs, and
        // its SVG support is unreliable — base64-embed the logo directly, raster only. No custom
        // logo uploaded, or an SVG one, falls back to the existing text-only header unchanged.
        $invoiceLogoDataUri = null;
        $logoPath = \App\Models\ShopSetting::current()->business_logo_path;
        if ($logoPath && preg_match('/\.(png|jpe?g)$/i', $logoPath, $m) && file_exists(public_path($logoPath))) {
            $mime = strtolower($m[1]) === 'png' ? 'image/png' : 'image/jpeg';
            $invoiceLogoDataUri = 'data:'.$mime.';base64,'.base64_encode(file_get_contents(public_path($logoPath)));
        }
    @endphp
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                @if ($invoiceLogoDataUri)
                    <img src="{{ $invoiceLogoDataUri }}" alt="Shri Vinayak Family Shop" style="height: 44px; margin-bottom: 6px;">
                @endif
                <p class="shop-name">Shri Vinayak Family Shop</p>
                <p class="shop-meta">
                    Roadways Bus Stand, Nichlaul Road, Siswa Bazar, Maharajganj, UP 273163<br>
                    @if ($businessPhone)
                        Phone: {{ $businessPhone['display'] }}<br>
                    @endif
                    Siswa Bazar's favourite grocery store since generations
                </p>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <p class="invoice-title">INVOICE</p>
                <p class="invoice-meta">
                    {{ $order->orderNumber() }}<br>
                    {{ $order->created_at->format('d M Y, h:i A') }}<br>
                    <span class="status-pill">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
                </p>
            </td>
        </tr>
    </table>

    <div class="bill-to">
        <p class="section-label">Billed To</p>
        <strong>{{ $order->customer_name }}</strong><br>
        {{ $order->customer_phone }}<br>
        {{ $order->delivery_address }}
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            {{-- removed (unavailable) items never appear on the invoice — subtotal/total are
                 already recalculated to exclude them, see Order::recalculateTotals() --}}
            @foreach ($order->items->whereNull('removed_at') as $item)
                <tr class="{{ $item->is_gift ? 'gift' : '' }}">
                    <td>{{ $item->product_name }}{{ $item->portionLabel() ? ' ('.$item->portionLabel().')' : '' }}{{ $item->is_gift ? ' (Free Gift)' : '' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $item->is_gift ? 'FREE' : 'Rs. '.number_format($item->product_price) }}</td>
                    <td class="num">{{ $item->is_gift ? 'FREE' : 'Rs. '.number_format($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">Rs. {{ number_format($order->subtotal) }}</td>
        </tr>
        @if ($order->discount_amount > 0)
            <tr>
                <td class="label">Coupon Discount{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }}</td>
                <td class="value">- Rs. {{ number_format($order->discount_amount) }}</td>
            </tr>
        @endif
        @foreach ($order->fees as $fee)
            <tr>
                <td class="label">{{ $fee->label }}</td>
                <td class="value">Rs. {{ number_format($fee->amount) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="value">Rs. {{ number_format($order->total) }}</td>
        </tr>
        @php
            // was previously hardcoded to "Cash on Delivery" regardless of how the order was
            // actually paid — fixed to read the real payment_method, and to surface the
            // "collect the difference on delivery" balance for an order that was paid online
            // before an admin added further items (see Order::balanceDueOnDelivery())
            $isPaidOnline = $order->payment_method === 'razorpay' && $order->payment_status === 'paid';
        @endphp
        <tr>
            <td class="label" style="padding-top: 10px;">Payment Method</td>
            <td class="value" style="padding-top: 10px;">
                @if ($isPaidOnline && $order->hasPostPaymentBalance())
                    Paid Online (Rs. {{ number_format($order->amount_paid) }}) - Balance on Delivery: Rs. {{ number_format($order->balanceDueOnDelivery()) }}
                @elseif ($isPaidOnline)
                    Paid Online
                @elseif ($order->payment_method === 'razorpay')
                    Paid Online ({{ ucfirst($order->payment_status) }})
                @else
                    Cash on Delivery
                @endif
            </td>
        </tr>
    </table>

    <div class="footer-note">
        @if ($businessPhone)
            Thank you for ordering from Shri Vinayak Family Shop! For any questions about this order, call us at {{ $businessPhone['display'] }}.<br>
        @else
            Thank you for ordering from Shri Vinayak Family Shop!<br>
        @endif
        This is a system-generated invoice and does not require a signature.
    </div>
</body>
</html>
