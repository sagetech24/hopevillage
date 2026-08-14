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
                    $wire.handleScanResult(result);
                    setTimeout(() => {
                        this.scanResult = null;
                        $wire.set('scanResult', null);
                        if (this.open && this.keepOpen) {
                            this.startScan();
                        }
                    }, 1500);
                    return;
                }
                $wire.close();
                setTimeout(() => {
                    $wire.handleScanResult(result);
                }, 500);
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
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[60] bg-black/60 flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
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
    
     <!-- Always include the components so they can listen to events -->
     <livewire:location-qr-code-modal :locationCode="null" />
     <livewire:event-qr-code-modal :eventCode="null" />
     <livewire:voucher-qr-code-modal :voucherCode="null" />
</div>