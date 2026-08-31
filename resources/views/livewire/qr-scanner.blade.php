<div>

    <div
        x-data="{
            open: @entangle('open').live,
            scanError: @entangle('scanError').live,
            scanResult: @entangle('scanResult').live,
            keepOpen: @entangle('keepOpen').live,
            stream: null,
            detector: null,
            scanTimer: null,
            canvas: null,
            context: null,
            useJsQR: false,
            facingMode: 'environment',
            awaitingChargeResult: false,
            _restartTimeout: null,
            async loadJsQR() {
                if (window.jsQR) return true;
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
                    script.onload = () => resolve(true);
                    script.onerror = () => reject(new Error('Failed to load jsQR library'));
                    document.head.appendChild(script);
                });
            },
            async startScan() {
                this.scanError = null;
                this.scanResult = null;
                $wire.set('scanError', null);
                $wire.set('scanResult', null);
    
                if (!navigator.mediaDevices?.getUserMedia) {
                    const error = 'Camera is not supported on this device/browser.';
                    this.scanError = error;
                    $wire.set('scanError', error);
                    return;
                }
    
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: this.facingMode } },
                        audio: false
                    });
                    this.$refs.qrVideo.srcObject = this.stream;
                    await this.$refs.qrVideo.play();
                } catch (e) {
                    const error = 'Camera permission denied or camera not available.';
                    this.scanError = error;
                    $wire.set('scanError', error);
                    return;
                }
    
                // Check if BarcodeDetector is available (Android Chrome, etc.)
                if ('BarcodeDetector' in window) {
                    try {
                        this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                        this.useJsQR = false;
                    } catch (e) {
                        // Fallback to jsQR if BarcodeDetector fails
                        this.useJsQR = true;
                    }
                } else {
                    // Use jsQR for iOS and other browsers
                    this.useJsQR = true;
                }
    
                // Load jsQR if needed
                if (this.useJsQR) {
                    try {
                        await this.loadJsQR();
                        // Create canvas for jsQR
                        if (!this.canvas) {
                            this.canvas = document.createElement('canvas');
                            this.context = this.canvas.getContext('2d');
                        }
                    } catch (e) {
                        const error = 'Failed to load QR scanner library.';
                        this.scanError = error;
                        $wire.set('scanError', error);
                        return;
                    }
                }
    
                // Start scanning
                this.scanTimer = setInterval(() => {
                    if (!this.$refs.qrVideo) return;
                    
                    if (this.useJsQR) {
                        // Use jsQR for iOS compatibility
                        this.scanWithJsQR();
                    } else {
                        // Use BarcodeDetector for supported browsers
                        this.scanWithBarcodeDetector();
                    }
                }, 300);
            },
             async scanWithBarcodeDetector() {
                 if (!this.detector || !this.$refs.qrVideo) return;
                 try {
                     const codes = await this.detector.detect(this.$refs.qrVideo);
                     if (codes && codes.length) {
                         const result = codes[0].rawValue || 'Scanned';
                         this.handleDetectedQr(result);
                     }
                 } catch (e) {
                     // Ignore transient detection errors
                 }
             },
             scanWithJsQR() {
                 if (!window.jsQR || !this.$refs.qrVideo || !this.canvas || !this.context) return;
                 
                 const video = this.$refs.qrVideo;
                 if (video.readyState === video.HAVE_ENOUGH_DATA) {
                     this.canvas.height = video.videoHeight;
                     this.canvas.width = video.videoWidth;
                     this.context.drawImage(video, 0, 0, this.canvas.width, this.canvas.height);
                     const imageData = this.context.getImageData(0, 0, this.canvas.width, this.canvas.height);
                     const code = jsQR(imageData.data, imageData.width, imageData.height);
                     
                     if (code) {
                         const result = code.data || 'Scanned';
                         this.handleDetectedQr(result);
                     }
                 }
             },
            handleDetectedQr(result) {
                this.scanResult = result;
                this.stopScan();
                if (this.keepOpen) {
                    this.awaitingChargeResult = true;
                    $wire.handleScanResult(result);
                    this._restartTimeout = setTimeout(() => {
                        this._restartTimeout = null;
                        this.scanResult = null;
                        $wire.set('scanResult', null);
                        if (this.open && this.keepOpen && !this.awaitingChargeResult) {
                            this.startScan();
                        }
                    }, 4000);
                    return;
                }
                $wire.close();
                setTimeout(() => {
                    $wire.handleScanResult(result);
                }, 500);
            },
            pauseForChargeResult() {
                this.awaitingChargeResult = true;
                if (this._restartTimeout) {
                    clearTimeout(this._restartTimeout);
                    this._restartTimeout = null;
                }
                this.scanResult = null;
                this.stopScan();
            },
            resumeAfterChargeResult() {
                this.awaitingChargeResult = false;
                this.scanResult = null;
                if (this._restartTimeout) {
                    clearTimeout(this._restartTimeout);
                    this._restartTimeout = null;
                }
                if (this.open && this.keepOpen) {
                    this.startScan();
                }
            },
            stopScan() {
                if (this.scanTimer) {
                    clearInterval(this.scanTimer);
                    this.scanTimer = null;
                }
                if (this.stream) {
                    this.stream.getTracks().forEach(t => t.stop());
                    this.stream = null;
                }
            },
            closeModal() {
                this.awaitingChargeResult = false;
                if (this._restartTimeout) {
                    clearTimeout(this._restartTimeout);
                    this._restartTimeout = null;
                }
                this.stopScan();
                $wire.close();
            },
            switchCamera() {
                this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
                this.stopScan();
                this.startScan();
            },
            init() {
                // Auto-start scan when modal opens
                this.$watch('open', (value) => {
                    if (value) {
                        this.awaitingChargeResult = false;
                        // Request geolocation when scanner opens (for location/event check-in validation)
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    $wire.setUserLocation(position.coords.latitude, position.coords.longitude);
                                },
                                () => { /* User denied or error - modal will handle */ },
                                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                            );
                        }
                        // Small delay to ensure video element is ready
                        setTimeout(() => {
                            this.startScan();
                        }, 100);
                    } else {
                        this.stopScan();
                    }
                });
            }
        }"
        @openQrScanner.window="$wire.open()"
        @openQrScannerKeepOpen.window="$wire.openKeepOpen()"
        @closeQrScanner.window="$wire.close()"
        @pause-qr-camera.window="pauseForChargeResult()"
        @resume-qr-camera.window="resumeAfterChargeResult()"
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[60] bg-black/60 flex items-center justify-center p-4"
        @keydown.escape.window="if (!awaitingChargeResult) closeModal()"
        @click.self="closeModal()"
        style="display: none;"
    >
        <div
            @click.stop
            class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 relative"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <!-- Close button -->
            <button
                @click="closeModal()"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
                aria-label="Close"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
    
            <!-- Title -->
            <h2 class="text-xl font-semibold text-gray-900 mb-2 pr-8">
                {{ $title }}
            </h2>
            
            @if($description)
            <p class="text-sm text-gray-600 mb-4">
                {{ $description }}
            </p>
            @endif
    
            <!-- Video container -->
            <div class="relative bg-black rounded-lg overflow-hidden mb-4" style="aspect-ratio: 1;">
                <video
                    x-ref="qrVideo"
                    autoplay
                    playsinline
                    class="w-full h-full object-cover"
                    x-show="!scanError"
                ></video>
                
                <!-- Scanning overlay -->
                <div
                    x-show="!scanError && !scanResult"
                    class="absolute inset-0 flex items-center justify-center pointer-events-none"
                >
                    <div class="border-2 border-white rounded-lg" style="width: 80%; height: 80%;">
                        <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-orange-500 rounded-tl-lg"></div>
                        <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-orange-500 rounded-tr-lg"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-orange-500 rounded-bl-lg"></div>
                        <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-orange-500 rounded-br-lg"></div>
                    </div>
                </div>

                <!-- Switch camera button -->
                <button
                    x-show="!scanError && !scanResult && stream"
                    @click="switchCamera()"
                    type="button"
                    class="absolute bottom-3 right-3 p-2 rounded-full bg-black/50 text-white hover:bg-black/70 transition pointer-events-auto"
                    :aria-label="facingMode === 'environment' ? 'Switch to front camera' : 'Switch to back camera'"
                    title="Switch camera"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
    
                <!-- Error message -->
                <div
                    x-show="scanError"
                    class="absolute inset-0 flex items-center justify-center bg-gray-900 text-white p-4"
                >
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm" x-text="scanError"></p>
                    </div>
                </div>
    
                <!-- Success message -->
                <div
                    x-show="scanResult"
                    class="absolute inset-0 flex items-center justify-center bg-green-900/90 text-white p-4"
                >
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-sm font-semibold">QR Code Scanned!</p>
                        <p class="text-xs mt-1 opacity-75" x-text="scanResult"></p>
                    </div>
                </div>
            </div>
    
            <!-- Action buttons -->
            <div class="flex gap-3">
                <button
                    @click="closeModal()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
                >
                    Close
                </button>
                <button
                    x-show="scanError"
                    @click="startScan()"
                    class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition font-medium"
                >
                    Retry
                </button>
            </div>
        </div>
    </div>

    <div
        x-data="{
            show: false,
            success: true,
            hasMember: false,
            memberName: '',
            balanceBefore: 0,
            deducted: 0,
            remaining: 0,
            message: '',
            items: [],
            seconds: 3,
            timer: null,
            parseDetail(event) {
                let d = event.detail;
                if (Array.isArray(d)) {
                    d = d[0] ?? {};
                }
                if (d && typeof d === 'object' && d.success === undefined && d[0] && typeof d[0] === 'object') {
                    d = d[0];
                }
                return d && typeof d === 'object' ? d : {};
            },
            openResult(event) {
                const d = this.parseDetail(event);
                this.success = !!d.success;
                this.hasMember = !!d.hasMember;
                this.memberName = d.memberName || '';
                this.balanceBefore = Number(d.balanceBefore || 0);
                this.deducted = Number(d.deducted || 0);
                this.remaining = Number(d.remaining || 0);
                this.message = d.message || '';
                this.items = Array.isArray(d.items) ? d.items : [];
                this.show = true;
                window.dispatchEvent(new CustomEvent('pause-qr-camera'));
                this.start();
            },
            start() {
                this.seconds = 5;
                this.clear();
                this.timer = setInterval(() => {
                    this.seconds--;
                    if (this.seconds <= 0) {
                        this.closeNow();
                    }
                }, 1000);
            },
            clear() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
            closeNow() {
                this.clear();
                this.show = false;
                window.dispatchEvent(new CustomEvent('resume-qr-camera'));
            },
            formatPts(value) {
                return new Intl.NumberFormat().format(Number(value || 0));
            }
        }"
        class="contents"
        @cashier-charge-result.window="openResult($event)"
    >
        <template x-teleport="body">
            <div
                x-show="show"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70"
                style="z-index: 10000;"
                @keydown.escape.window="if (show) closeNow()"
                role="dialog"
                aria-modal="true"
            >
                <div @click.stop class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative z-[10000]">
                    <div class="flex flex-col items-center text-center">
                        <template x-if="success">
                            <div>
                                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-3 mx-auto">
                                    <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-green-700">{{ __('Success') }}</h3>
                            </div>
                        </template>
                        <template x-if="!success">
                            <div>
                                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-3 mx-auto">
                                    <svg class="w-9 h-9 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-red-700">{{ __('Fail') }}</h3>
                                <p class="mt-2 text-sm text-red-600" x-show="message" x-text="message"></p>
                            </div>
                        </template>
                    </div>

                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">{{ __('Member') }}</dt>
                            <dd class="font-semibold text-gray-900 capitalize text-right" x-text="hasMember ? memberName : '{{ __('Unknown member') }}'"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">{{ __('Points balance') }}</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums" x-text="hasMember ? (formatPts(balanceBefore) + ' {{ __('pts') }}') : '—'"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">{{ __('Deducted points') }}</dt>
                            <dd class="font-semibold tabular-nums" :class="success ? 'text-orange-600' : 'text-gray-900'" x-text="formatPts(deducted) + ' {{ __('pts') }}'"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">{{ __('Remaining balance') }}</dt>
                            <dd class="font-bold text-lg tabular-nums" :class="success ? 'text-green-700' : 'text-gray-900'" x-text="hasMember ? (formatPts(remaining) + ' {{ __('pts') }}') : '—'"></dd>
                        </div>
                    </dl>

                    {{-- <div x-show="items.length" class="mt-4 border border-gray-100 rounded-lg divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                <p class="min-w-0 text-gray-800">
                                    <span class="font-medium capitalize" x-text="item.name"></span>
                                    <span class="text-gray-500" x-text="' × ' + item.qty"></span>
                                </p>
                                <p class="shrink-0 tabular-nums text-orange-600" x-text="formatPts(item.points) + ' {{ __('pts') }}'"></p>
                            </div>
                        </template>
                    </div> --}}

                    {{-- <p class="mt-5 text-center text-sm text-gray-500" aria-live="polite">
                        {{ __('This window will close in') }}
                        <span class="font-semibold text-gray-800 tabular-nums">
                            <span x-show="seconds >= 5">5...</span><span x-show="seconds >= 4">4...</span><span x-show="seconds >= 3">3...</span><span x-show="seconds >= 2">2...</span><span x-show="seconds >= 1">1...</span>
                        </span>
                    </p> --}}

                    <button
                        type="button"
                        @click="closeNow()"
                        class="mt-4 w-full px-4 py-2.5 bg-slate-400 text-white rounded-lg hover:bg-slate-500 text-xs font-medium"
                    >
                        {{ __('Click to close or it will close in') }}    
                        <span x-text="seconds"></span>
                    </button>
                </div>
            </div>
        </template>
    </div>
    
     <!-- Always include the components so they can listen to events -->
     <livewire:location-qr-code-modal :locationCode="null" />
     <livewire:event-qr-code-modal :eventCode="null" />
     <livewire:voucher-qr-code-modal :voucherCode="null" />
</div>