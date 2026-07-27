{{-- On mobile this behaves as a bottom sheet — slides up from off-screen, rounded top corners
     only, dismissible by swiping down on the handle/image, tapping the backdrop, or the close
     button (see partials/announcement-banner-content.blade.php). On sm+ it becomes a centered,
     fully-rounded modal instead — same panel, same transition timing, just repositioned/resized
     via sm: classes (same technique as partials/legal-document-modal.blade.php's width/height).
     Colors: the backdrop/handle here use the sitewide maroon/gold theme so the chrome always
     matches whichever customer theme is active; the content card's own bg/text stay
     admin-configurable per announcement (unchanged from before). --}}
@if ($announcement)
    <div x-data="announcementBanner(@js($announcement))" x-show="open" x-cloak
         @keydown.escape.window="open && dismiss()"
         class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-maroon-900/70 backdrop-blur-sm"
             x-transition:enter="transition-opacity duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="dismiss()"></div>

        <div x-ref="panel"
             x-transition:enter="transition duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition duration-[250ms] ease-in" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-6 sm:scale-95"
             :style="dragging ? `transform: translateY(${dragDeltaY}px)` : (snapping ? `transform: translateY(${dragDeltaY}px); transition: transform 220ms cubic-bezier(0.32,0.72,0,1);` : '')"
             class="relative w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl bg-ivory shadow-2xl overflow-hidden max-h-[88vh] sm:max-h-[85vh] flex flex-col will-change-transform">

            {{-- swipe-to-dismiss zone: the handle + image only, so a tap on the CTA/close button
                 (or a long description below) never gets mistaken for a drag gesture --}}
            <div class="sm:hidden absolute inset-x-0 top-0 z-[5] h-14 flex justify-center pt-2.5"
                 @touchstart="onDragStart($event)" @touchmove="onDragMove($event)" @touchend="onDragEnd()">
                <span class="w-10 h-1.5 rounded-full bg-white/80 shadow-sm"></span>
            </div>

            <div class="flex-1 overflow-y-auto overscroll-contain">
                @include('partials.announcement-banner-content')
            </div>
        </div>
    </div>
@endif
