@php
    $orderStatusLabels = [
        'pending' => __('Pending'),
        'confirmed' => __('Confirmed'),
        'out_for_delivery' => __('Out for Delivery'),
        'delivered' => __('Delivered'),
        'cancelled' => __('Cancelled'),
    ];
@endphp
<script>
    {{-- @json() mis-splits on every comma in the raw expression text (a real Laravel bug — see
         CompilesJson::compileJson()), which corrupts any array literal with more than one entry;
         json_encode() in a plain {{ }} expression sidesteps it entirely --}}
    window.ORDER_STATUS_LABELS = {!! json_encode($orderStatusLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>
