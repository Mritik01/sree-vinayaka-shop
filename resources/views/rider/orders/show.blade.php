@extends('rider.layout')

@section('title', __('Order #') . $order->id)

@section('content')
    @php
        $mapDestination = ($order->latitude !== null && $order->longitude !== null)
            ? $order->latitude . ',' . $order->longitude
            : $order->delivery_address;
    @endphp

    <a href="{{ route('rider.orders.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← {{ __('Back to Deliveries') }}</a>

    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 mt-3">
        <div class="flex items-start justify-between gap-3">
            <p class="font-display text-lg text-maroon-800">{{ __('Order #') }}{{ $order->id }}</p>
            <x-admin.status-badge :status="$order->status" />
        </div>

        <div class="mt-4 pt-4 border-t border-gold-100">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Customer') }}</p>
            <p class="text-sm text-maroon-800 font-medium mt-1">{{ $order->customer_name }}</p>
            <a href="tel:+91{{ preg_replace('/\D/', '', $order->customer_phone) }}" class="text-sm text-gold-600 hover:text-gold-700 underline underline-offset-2">
                📞 {{ $order->customer_phone }}
            </a>
        </div>

        <div class="mt-4 pt-4 border-t border-gold-100">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Delivery Address') }}</p>
            <p class="text-sm text-maroon-700 mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
            @if ($order->distance_km !== null)
                <p class="text-xs text-maroon-500 mt-1.5">📍 {{ number_format($order->distance_km, 1) }} {{ __('km from shop') }}</p>
            @endif
            <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($mapDestination) }}" target="_blank" rel="noopener"
               class="mt-3 flex items-center justify-center gap-2 w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl py-2.5 transition text-sm">
                🗺️ {{ __('View on Map') }}
            </a>
        </div>

        @if ($order->customer_note)
            <div class="mt-4 pt-4 border-t border-gold-100">
                <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">📝 {{ __('Customer Note') }}</p>
                <p class="text-sm text-maroon-700 mt-1 whitespace-pre-line">{{ $order->customer_note }}</p>
            </div>
        @endif

        <div class="mt-4 pt-4 border-t border-gold-100">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold mb-2">{{ __('Items') }}</p>
            <div class="space-y-1.5">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-maroon-700">{{ $item->product_name }} × {{ $item->quantity }}</span>
                        <span class="text-maroon-500">₹{{ number_format($item->line_total) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gold-100 font-semibold">
                <span class="text-maroon-800">{{ __('Total') }} ({{ strtoupper($order->payment_method ?? 'COD') }})</span>
                <span class="text-maroon-800">₹{{ number_format($order->total) }}</span>
            </div>
        </div>
    </div>

    {{-- status action --}}
    @if ($order->status === 'confirmed')
        <form method="POST" action="{{ route('rider.orders.status', $order) }}" class="mt-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="out_for_delivery">
            <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl py-3 transition">🛵 {{ __('Start Delivery') }}</button>
        </form>
    @elseif ($order->status === 'out_for_delivery')
        <form method="POST" action="{{ route('rider.orders.status', $order) }}" class="mt-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="delivered">
            <button type="submit" class="w-full bg-maroon-700 hover:bg-maroon-800 text-cream font-semibold rounded-xl py-3 transition">✓ {{ __('Mark Delivered') }}</button>
        </form>
    @elseif ($order->status === 'delivered')
        <p class="mt-4 text-center text-sm text-pista-600 font-medium">✓ {{ __('Delivered') }} {{ $order->delivered_at?->format('d M, h:i A') }}</p>
    @endif

    {{-- proof-of-delivery photo --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 mt-4">
        <p class="font-display text-maroon-800 mb-3">📸 {{ __('Delivery Photo') }}</p>

        @if ($order->delivery_photo_path)
            <img src="{{ asset($order->delivery_photo_path) }}" alt="Delivery proof for order #{{ $order->id }}" class="w-full rounded-xl border border-gold-200/60">
            <p class="text-xs text-maroon-400 mt-2">{{ __('Uploaded') }} {{ $order->delivery_photo_uploaded_at?->format('d M, h:i A') }} — {{ __('will auto-remove after 3 days.') }}</p>
        @else
            <p class="text-xs text-maroon-400 mb-3">{{ __('Take a photo at the doorstep as delivery proof.') }}</p>
        @endif

        <button type="button" id="openCameraBtn" class="mt-3 block w-full text-center bg-gold-500 hover:bg-gold-600 text-maroon-900 font-semibold rounded-xl py-3 transition">
            📷 {{ $order->delivery_photo_path ? __('Retake Photo') : __('Take Photo') }}
        </button>

        {{-- fallback for browsers/devices that can't use a live camera stream --}}
        <form id="fallbackForm" method="POST" action="{{ route('rider.orders.photo', $order) }}" enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" id="fallbackInput" name="photo" accept="image/*" capture="environment">
        </form>
    </div>

    {{-- live camera capture modal --}}
    <div id="cameraModal" class="hidden fixed inset-0 z-50 bg-black flex flex-col">
        <video id="cameraVideo" autoplay playsinline muted class="flex-1 min-h-0 w-full object-cover"></video>
        <canvas id="cameraCanvas" class="hidden"></canvas>
        <img id="capturedPreview" class="hidden flex-1 min-h-0 w-full object-cover">

        <div id="cameraError" class="hidden flex-1 flex items-center justify-center text-cream text-center px-6 text-sm"></div>

        <div class="shrink-0 bg-black/90 px-5 py-5 flex items-center justify-center gap-4">
            <button type="button" id="cancelCameraBtn" class="text-cream/70 hover:text-cream text-sm font-medium px-4 py-3">{{ __('Cancel') }}</button>
            <button type="button" id="captureBtn" class="w-16 h-16 rounded-full bg-white border-4 border-cream/40 active:scale-90 transition"></button>
            <button type="button" id="retakeBtn" class="hidden text-cream/70 hover:text-cream text-sm font-medium px-4 py-3">{{ __('Retake') }}</button>
            <button type="button" id="usePhotoBtn" class="hidden bg-gold-500 hover:bg-gold-600 text-maroon-900 font-semibold rounded-xl px-5 py-3 text-sm">✓ {{ __('Use Photo') }}</button>
        </div>
    </div>

    <script>
        (function () {
            const i18n = {
                cameraError: @json(__("Couldn't access the camera (permission denied or unavailable). Use the button below to choose a photo instead.")),
                uploading: @json(__('Uploading…')),
                usePhoto: @json('✓ ' . __('Use Photo')),
                uploadFailed: @json(__("Couldn't upload the photo — check your connection and try again.")),
            };
            const openBtn = document.getElementById('openCameraBtn');
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const preview = document.getElementById('capturedPreview');
            const errorBox = document.getElementById('cameraError');
            const captureBtn = document.getElementById('captureBtn');
            const retakeBtn = document.getElementById('retakeBtn');
            const usePhotoBtn = document.getElementById('usePhotoBtn');
            const cancelBtn = document.getElementById('cancelCameraBtn');
            const fallbackForm = document.getElementById('fallbackForm');
            const fallbackInput = document.getElementById('fallbackInput');

            let stream = null;
            let capturedBlob = null;

            function stopStream() {
                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                }
            }

            function resetModal() {
                canvas.classList.add('hidden');
                preview.classList.add('hidden');
                errorBox.classList.add('hidden');
                video.classList.remove('hidden');
                captureBtn.classList.remove('hidden');
                retakeBtn.classList.add('hidden');
                usePhotoBtn.classList.add('hidden');
                capturedBlob = null;
            }

            async function openCamera() {
                modal.classList.remove('hidden');
                resetModal();

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    // unsupported browser — fall back to the native file picker straight away
                    closeCamera();
                    fallbackInput.click();
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    video.srcObject = stream;
                } catch (e) {
                    video.classList.add('hidden');
                    captureBtn.classList.add('hidden');
                    errorBox.textContent = i18n.cameraError;
                    errorBox.classList.remove('hidden');
                }
            }

            function closeCamera() {
                stopStream();
                modal.classList.add('hidden');
            }

            function capturePhoto() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                canvas.toBlob((blob) => {
                    capturedBlob = blob;
                    preview.src = URL.createObjectURL(blob);
                    video.classList.add('hidden');
                    preview.classList.remove('hidden');
                    captureBtn.classList.add('hidden');
                    retakeBtn.classList.remove('hidden');
                    usePhotoBtn.classList.remove('hidden');
                }, 'image/jpeg', 0.9);
            }

            async function uploadPhoto() {
                if (!capturedBlob) return;
                usePhotoBtn.disabled = true;
                usePhotoBtn.textContent = i18n.uploading;

                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const formData = new FormData();
                formData.append('photo', capturedBlob, 'delivery-photo.jpg');

                try {
                    const res = await fetch(@json(route('rider.orders.photo', $order)), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    if (res.ok) {
                        window.location.reload();
                        return;
                    }
                } catch (e) {}
                usePhotoBtn.disabled = false;
                usePhotoBtn.textContent = i18n.usePhoto;
                alert(i18n.uploadFailed);
            }

            openBtn.addEventListener('click', openCamera);
            cancelBtn.addEventListener('click', closeCamera);
            captureBtn.addEventListener('click', capturePhoto);
            retakeBtn.addEventListener('click', resetModal);
            usePhotoBtn.addEventListener('click', uploadPhoto);
            fallbackInput.addEventListener('change', () => fallbackForm.submit());
        })();
    </script>
@endsection
