@php
    $styles = [
        'pending' => 'bg-gold-100 text-gold-600 border-gold-300/60',
        'confirmed' => 'bg-pista-100 text-pista-600 border-pista-400/40',
        'out_for_delivery' => 'bg-sky-50 text-sky-600 border-sky-200',
        'delivered' => 'bg-maroon-100 text-maroon-600 border-maroon-400/30',
        'cancelled' => 'bg-red-50 text-red-600 border-red-200',
    ];
    $labels = [
        'pending' => __('Pending'),
        'confirmed' => __('Confirmed'),
        'out_for_delivery' => __('Out for Delivery'),
        'delivered' => __('Delivered'),
        'cancelled' => __('Cancelled'),
    ];
@endphp
<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border {{ $styles[$status] ?? $styles['pending'] }}">
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
