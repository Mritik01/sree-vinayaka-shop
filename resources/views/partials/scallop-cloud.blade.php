{{-- Ornate scalloped cloud divider (rounded bumps, not zigzag). Usage:
     @include('partials.scallop-cloud', ['fill' => '#fdf6e9'])                 — bumps pointing up (place at bottom of a photo, fill = card colour below)
     @include('partials.scallop-cloud', ['fill' => '#fdf6e9', 'flip' => true]) — bumps pointing down
--}}
@php
    $fill = $fill ?? '#fdf6e9';
    $flip = $flip ?? false;
    $bumps = $bumps ?? 6;
    $w = 1200 / $bumps;
    $d = 'M0 40';
    for ($i = 0; $i < $bumps; $i++) {
        $peak = $i * $w + $w / 2;
        $end = ($i + 1) * $w;
        $d .= " Q {$peak} -18 {$end} 40";
    }
    $d .= ' L1200 70 L0 70 Z';
@endphp
<div class="leading-[0] {{ $flip ? 'rotate-180' : '' }} -my-px relative z-10">
    <svg class="w-full h-8 sm:h-10 block" viewBox="0 0 1200 70" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="{{ $fill }}" d="{{ $d }}" />
    </svg>
</div>
