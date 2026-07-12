{{-- Zigzag "sweet-box trim" divider. Usage:
     @include('partials.trim', ['fill' => '#fdf6e9'])            — points upward (place at bottom of a section, fill = NEXT section's bg)
     @include('partials.trim', ['fill' => '#fffbf2', 'flip' => true]) — points downward (place at top of a section, fill = PREVIOUS section's bg)
--}}
@php
    $fill = $fill ?? '#fdf6e9';
    $flip = $flip ?? false;
    $segments = 40;
    $w = 1200 / $segments;
    $d = 'M0 24 L0 14';
    for ($i = 0; $i < $segments; $i++) {
        $peak = $i * $w + $w / 2;
        $end = ($i + 1) * $w;
        $d .= " L{$peak} 2 L{$end} 14";
    }
    $d .= ' L1200 24 Z';
@endphp
<div class="leading-[0] {{ $flip ? 'rotate-180' : '' }} -my-px relative z-10">
    <svg class="w-full h-3 sm:h-4 block" viewBox="0 0 1200 24" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="{{ $fill }}" d="{{ $d }}" />
    </svg>
</div>
