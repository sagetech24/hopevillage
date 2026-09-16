<div>
    @can('location.profile')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
                <div class="flex md:flex-row flex-col md:gap-0 gap-4 md:justify-between justify-center items-center">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight text-center md:text-left">
                        {{ $location->name }}
                    </h2>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.locations.index') }}" class="text-orange-600 md:text-base text-sm hover:text-orange-700 font-normal py-2 px-4 rounded-full">
                            ← Back to Locations
                        </a>
                    </div>
                </div>
            </div>
        </x-slot>

        <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
            <div class="my-4 grid grid-cols-1 lg:grid-cols-3 gap-6 md:mx-0 mx-4">
                <!-- Left Column - Location Details -->
                <div class="lg:col-span-2 space-y-6">
                    @php
                        // Map thumbnail fallback if no uploaded thumbnail
                        $mapThumbnailUrl = null;
                        if (!$location->thumbnail_url) {
                            $apiKey = config('services.google_maps.api_key');
                            if ($apiKey) {
                                if (isset($location->latitude) && isset($location->longitude) && $location->latitude && $location->longitude) {
                                    $lat = $location->latitude;
                                    $lng = $location->longitude;
                                    $mapThumbnailUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size=1200x450&markers=color:red|{$lat},{$lng}&key={$apiKey}";
                                } elseif ($location->address) {
                                    $address = urlencode(trim($location->address . ', ' . $location->city . ', ' . $location->province . ', ' . $location->postal_code, ', '));
                                    $mapThumbnailUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=15&size=1200x450&markers=color:red|{$address}&key={$apiKey}";
                                }
                            }
                        }
                    @endphp

                    @if($location->thumbnail_url || $mapThumbnailUrl)
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg">
                            <img
                                src="{{ $location->thumbnail_url ?: $mapThumbnailUrl }}"
                                alt="{{ $location->name }} {{ $location->thumbnail_url ? 'thumbnail' : 'map' }}"
                                class="w-full h-72 object-cover"
                                loading="lazy"
                            >
                        </div>
                    @endif

                    <!-- Location Information Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Location Details</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-gray-900">{{ $location->name }}</p>
                            </div>
                            
                            @if($location->description)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Description</label>
                                <p class="text-gray-900">{{ $location->description }}</p>
                            </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p>
                                        <span class="px-3 py-1 inline-flex text-md leading-5 font-semibold rounded-full {{ $location->is_active ? 'bg-green-100 text-green-800 border border-green-500' : 'bg-red-100 text-red-800 border border-red-500' }}">
                                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Quick Actions & Recent Events -->
                <div class="space-y-6">
                    <!-- Location QR Code Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <div 
                            x-data="{
                                qrCodeImage: '{{ $qrCodeImage }}',
                                locationCode: '{{ $location->location_code }}',
                                async downloadQR() {
                                    try {
                                        const response = await fetch(this.qrCodeImage);
                                        const blob = await response.blob();
                                        const url = window.URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = `location-qr-${this.locationCode}.png`;
                                        document.body.appendChild(a);
                                        a.click();
                                        window.URL.revokeObjectURL(url);
                                        document.body.removeChild(a);
                                    } catch (error) {
                                        console.error('Download failed:', error);
                                        alert('Failed to download QR code. Please try again.');
                                    }
                                },
                                async shareQR() {
                                    try {
                                        if (navigator.share) {
                                            const response = await fetch(this.qrCodeImage);
                                            const blob = await response.blob();
                                            const file = new File([blob], `location-qr-${this.locationCode}.png`, { type: 'image/png' });
                                            await navigator.share({
                                                title: 'Location QR Code: {{ $location->name }}',
                                                text: `Location Code: ${this.locationCode}`,
                                                files: [file]
                                            });
                                        } else if (navigator.clipboard) {
                                            await navigator.clipboard.writeText(this.locationCode);
                                            alert('Location code copied to clipboard!');
                                        } else {
                                            // Fallback: copy location code to clipboard manually
                                            const textArea = document.createElement('textarea');
                                            textArea.value = this.locationCode;
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);
                                            alert('Location code copied to clipboard!');
                                        }
                                    } catch (error) {
                                        console.error('Share failed:', error);
                                        // Fallback to copy location code
                                        try {
                                            await navigator.clipboard.writeText(this.locationCode);
                                            alert('Location code copied to clipboard!');
                                        } catch (e) {
                                            alert('Sharing not available. Location Code: ' + this.locationCode);
                                        }
                                    }
                                }
                            }"
                        >
                            <div class="flex items-center justify-center mb-4">
                                <img :src="qrCodeImage" alt="Location QR Code" class="w-full h-50 object-contain" id="location-qr-image">
                            </div>
                            <div class="flex gap-3 justify-center">
                                <button 
                                    @click="downloadQR()"
                                    class="flex lg:flex-col flex-row items-center text-xs gap-1 p-2 bg-transparent hover:bg-gray-200 cursor-pointer text-gray-500 border border-gray-500 font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Download
                                </button>
                                <button 
                                    @click="shareQR()"
                                    class="flex lg:flex-col flex-row items-center text-xs gap-1 py-2 px-5 bg-green-600 hover:bg-green-700 cursor-pointer text-white border border-green-600 font-medium rounded-lg transition-colors duration-200"
                                >
                                    {{-- copy icon --}}
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                    </svg>
                                    Share
                                </button>
                                <button 
                                    type="button"
                                    onclick="openQrScanner()"
                                    class="flex lg:flex-col flex-row text-xs cursor-pointer items-center justify-center gap-1 py-2 px-4 bg-orange-500 hover:bg-orange-600 text-white text-center font-semibold rounded-lg transition-all duration-200"
                                >
                                    <svg class="size-5" fill="#ffffff" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.1666667,6 C16.0746192,6 16,6.07461921 16,6.16666667 L16,7.83333333 C16,7.92538079 16.0746192,8 16.1666667,8 L17.8333333,8 C17.9253808,8 18,7.92538079 18,7.83333333 L18,6.16666667 C18,6.07461921 17.9253808,6 17.8333333,6 L16.1666667,6 Z M16,18 L16,17.5 C16,17.2238576 16.2238576,17 16.5,17 C16.7761424,17 17,17.2238576 17,17.5 L17,18 L18,18 L18,17.5 C18,17.2238576 18.2238576,17 18.5,17 C18.7761424,17 19,17.2238576 19,17.5 L19,18.5 C19,18.7761424 18.7761424,19 18.5,19 L14.5,19 C14.2238576,19 14,18.7761424 14,18.5 L14,17.5 C14,17.2238576 14.2238576,17 14.5,17 C14.7761424,17 15,17.2238576 15,17.5 L15,18 L16,18 L16,18 Z M13,11 L13.5,11 C13.7761424,11 14,11.2238576 14,11.5 C14,11.7761424 13.7761424,12 13.5,12 L11.5,12 C11.2238576,12 11,11.7761424 11,11.5 C11,11.2238576 11.2238576,11 11.5,11 L12,11 L12,10 L10.5,10 C10.2238576,10 10,9.77614237 10,9.5 C10,9.22385763 10.2238576,9 10.5,9 L13.5,9 C13.7761424,9 14,9.22385763 14,9.5 C14,9.77614237 13.7761424,10 13.5,10 L13,10 L13,11 Z M18,12 L17.5,12 C17.2238576,12 17,11.7761424 17,11.5 C17,11.2238576 17.2238576,11 17.5,11 L18,11 L18,10.5 C18,10.2238576 18.2238576,10 18.5,10 C18.7761424,10 19,10.2238576 19,10.5 L19,12.5 C19,12.7761424 18.7761424,13 18.5,13 C18.2238576,13 18,12.7761424 18,12.5 L18,12 Z M13,14 L12.5,14 C12.2238576,14 12,13.7761424 12,13.5 C12,13.2238576 12.2238576,13 12.5,13 L13.5,13 C13.7761424,13 14,13.2238576 14,13.5 L14,15.5 C14,15.7761424 13.7761424,16 13.5,16 L10.5,16 C10.2238576,16 10,15.7761424 10,15.5 C10,15.2238576 10.2238576,15 10.5,15 L13,15 L13,14 L13,14 Z M16.1666667,5 L17.8333333,5 C18.4776655,5 19,5.52233446 19,6.16666667 L19,7.83333333 C19,8.47766554 18.4776655,9 17.8333333,9 L16.1666667,9 C15.5223345,9 15,8.47766554 15,7.83333333 L15,6.16666667 C15,5.52233446 15.5223345,5 16.1666667,5 Z M6.16666667,5 L7.83333333,5 C8.47766554,5 9,5.52233446 9,6.16666667 L9,7.83333333 C9,8.47766554 8.47766554,9 7.83333333,9 L6.16666667,9 C5.52233446,9 5,8.47766554 5,7.83333333 L5,6.16666667 C5,5.52233446 5.52233446,5 6.16666667,5 Z M6.16666667,6 C6.07461921,6 6,6.07461921 6,6.16666667 L6,7.83333333 C6,7.92538079 6.07461921,8 6.16666667,8 L7.83333333,8 C7.92538079,8 8,7.92538079 8,7.83333333 L8,6.16666667 C8,6.07461921 7.92538079,6 7.83333333,6 L6.16666667,6 Z M6.16666667,15 L7.83333333,15 C8.47766554,15 9,15.5223345 9,16.1666667 L9,17.8333333 C9,18.4776655 8.47766554,19 7.83333333,19 L6.16666667,19 C5.52233446,19 5,18.4776655 5,17.8333333 L5,16.1666667 C5,15.5223345 5.52233446,15 6.16666667,15 Z M6.16666667,16 C6.07461921,16 6,16.0746192 6,16.1666667 L6,17.8333333 C6,17.9253808 6.07461921,18 6.16666667,18 L7.83333333,18 C7.92538079,18 8,17.9253808 8,17.8333333 L8,16.1666667 C8,16.0746192 7.92538079,16 7.83333333,16 L6.16666667,16 Z M13,6 L10.5,6 C10.2238576,6 10,5.77614237 10,5.5 C10,5.22385763 10.2238576,5 10.5,5 L13.5,5 C13.7761424,5 14,5.22385763 14,5.5 L14,7.5 C14,7.77614237 13.7761424,8 13.5,8 C13.2238576,8 13,7.77614237 13,7.5 L13,6 Z M10.5,8 C10.2238576,8 10,7.77614237 10,7.5 C10,7.22385763 10.2238576,7 10.5,7 L11.5,7 C11.7761424,7 12,7.22385763 12,7.5 C12,7.77614237 11.7761424,8 11.5,8 L10.5,8 Z M5.5,14 C5.22385763,14 5,13.7761424 5,13.5 C5,13.2238576 5.22385763,13 5.5,13 L7.5,13 C7.77614237,13 8,13.2238576 8,13.5 C8,13.7761424 7.77614237,14 7.5,14 L5.5,14 Z M9.5,14 C9.22385763,14 9,13.7761424 9,13.5 C9,13.2238576 9.22385763,13 9.5,13 L10.5,13 C10.7761424,13 11,13.2238576 11,13.5 C11,13.7761424 10.7761424,14 10.5,14 L9.5,14 Z M11,18 L11,18.5 C11,18.7761424 10.7761424,19 10.5,19 C10.2238576,19 10,18.7761424 10,18.5 L10,17.5 C10,17.2238576 10.2238576,17 10.5,17 L12.5,17 C12.7761424,17 13,17.2238576 13,17.5 C13,17.7761424 12.7761424,18 12.5,18 L11,18 Z M9,11 L9.5,11 C9.77614237,11 10,11.2238576 10,11.5 C10,11.7761424 9.77614237,12 9.5,12 L8.5,12 C8.22385763,12 8,11.7761424 8,11.5 L8,11 L7.5,11 C7.22385763,11 7,10.7761424 7,10.5 C7,10.2238576 7.22385763,10 7.5,10 L8.5,10 C8.77614237,10 9,10.2238576 9,10.5 L9,11 Z M5,10.5 C5,10.2238576 5.22385763,10 5.5,10 C5.77614237,10 6,10.2238576 6,10.5 L6,11.5 C6,11.7761424 5.77614237,12 5.5,12 C5.22385763,12 5,11.7761424 5,11.5 L5,10.5 Z M15,10.5 C15,10.2238576 15.2238576,10 15.5,10 C15.7761424,10 16,10.2238576 16,10.5 L16,12.5 C16,12.7761424 15.7761424,13 15.5,13 C15.2238576,13 15,12.7761424 15,12.5 L15,10.5 Z M17,15 L17,14.5 C17,14.2238576 17.2238576,14 17.5,14 L18.5,14 C18.7761424,14 19,14.2238576 19,14.5 C19,14.7761424 18.7761424,15 18.5,15 L18,15 L18,15.5 C18,15.7761424 17.7761424,16 17.5,16 L15.5,16 C15.2238576,16 15,15.7761424 15,15.5 L15,14.5 C15,14.2238576 15.2238576,14 15.5,14 C15.7761424,14 16,14.2238576 16,14.5 L16,15 L17,15 Z M3,6.5 C3,6.77614237 2.77614237,7 2.5,7 C2.22385763,7 2,6.77614237 2,6.5 L2,4.5 C2,3.11928813 3.11928813,2 4.5,2 L6.5,2 C6.77614237,2 7,2.22385763 7,2.5 C7,2.77614237 6.77614237,3 6.5,3 L4.5,3 C3.67157288,3 3,3.67157288 3,4.5 L3,6.5 Z M17.5,3 C17.2238576,3 17,2.77614237 17,2.5 C17,2.22385763 17.2238576,2 17.5,2 L19.5,2 C20.8807119,2 22,3.11928813 22,4.5 L22,6.5 C22,6.77614237 21.7761424,7 21.5,7 C21.2238576,7 21,6.77614237 21,6.5 L21,4.5 C21,3.67157288 20.3284271,3 19.5,3 L17.5,3 Z M6.5,21 C6.77614237,21 7,21.2238576 7,21.5 C7,21.7761424 6.77614237,22 6.5,22 L4.5,22 C3.11928813,22 2,20.8807119 2,19.5 L2,17.5 C2,17.2238576 2.22385763,17 2.5,17 C2.77614237,17 3,17.2238576 3,17.5 L3,19.5 C3,20.3284271 3.67157288,21 4.5,21 L6.5,21 Z M21,17.5 C21,17.2238576 21.2238576,17 21.5,17 C21.7761424,17 22,17.2238576 22,17.5 L22,19.5 C22,20.8807119 20.8807119,22 19.5,22 L17.5,22 C17.2238576,22 17,21.7761424 17,21.5 C17,21.2238576 17.2238576,21 17.5,21 L19.5,21 C20.3284271,21 21,20.3284271 21,19.5 L21,17.5 Z"></path> </g>
                                    </svg>
                                    QR Scan
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Contact Information</h3>
                        
                        <div class="space-y-4">
                            @if($location->address)
                            <div class="flex items-start gap-1">
                                <svg class="size-5" version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve" fill="#000000">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path fill="#394240" d="M32,0C18.745,0,8,10.745,8,24c0,5.678,2.502,10.671,5.271,15l17.097,24.156C30.743,63.686,31.352,64,32,64 s1.257-0.314,1.632-0.844L50.729,39C53.375,35.438,56,29.678,56,24C56,10.745,45.255,0,32,0z M48.087,39h-0.01L32,61L15.923,39 h-0.01C13.469,35.469,10,29.799,10,24c0-12.15,9.85-22,22-22s22,9.85,22,22C54,29.799,50.281,35.781,48.087,39z"></path> <path fill="#394240" d="M32,14c-5.523,0-10,4.478-10,10s4.477,10,10,10s10-4.478,10-10S37.523,14,32,14z M32,32 c-4.418,0-8-3.582-8-8s3.582-8,8-8s8,3.582,8,8S36.418,32,32,32z"></path> <path fill="#394240" d="M32,10c-7.732,0-14,6.268-14,14s6.268,14,14,14s14-6.268,14-14S39.732,10,32,10z M32,36 c-6.627,0-12-5.373-12-12s5.373-12,12-12s12,5.373,12,12S38.627,36,32,36z"></path> </g> <g> <path fill="#F76D57" d="M32,12c-6.627,0-12,5.373-12,12s5.373,12,12,12s12-5.373,12-12S38.627,12,32,12z M32,34 c-5.522,0-10-4.477-10-10s4.478-10,10-10s10,4.477,10,10S37.522,34,32,34z"></path> <path fill="#F76D57" d="M32,2c-12.15,0-22,9.85-22,22c0,5.799,3.469,11.469,5.913,15h0.01L32,61l16.077-22h0.01 C50.281,35.781,54,29.799,54,24C54,11.85,44.15,2,32,2z M32,38c-7.732,0-14-6.268-14-14s6.268-14,14-14s14,6.268,14,14 S39.732,38,32,38z"></path> </g> <path opacity="0.2" fill="#231F20" d="M32,12c-6.627,0-12,5.373-12,12s5.373,12,12,12s12-5.373,12-12S38.627,12,32,12z M32,34 c-5.522,0-10-4.477-10-10s4.478-10,10-10s10,4.477,10,10S37.522,34,32,34z"></path> </g> </g>
                                </svg>
                                <div>
                                    <p class="text-gray-900">
                                        {{ $location->address }}
                                        @if($location->city || $location->province || $location->postal_code)
                                            <br>
                                            {{ trim(implode(', ', array_filter([$location->city, $location->province, $location->postal_code]))) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif

                            @if($location->phone)
                            <div class="flex items-start gap-1">
                                <svg class="size-5" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 500 500" xml:space="preserve" fill="#000000">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .st0{fill:#FFFFFF;stroke:#FFFFFF;stroke-width:10;stroke-miterlimit:10;} .st1{fill:#343434;} .st2{fill:#8ECDFC;} .st3{fill:#E9E9E9;} .st4{fill:#A8F1E5;} </style> <g id="border"> <path class="st0" d="M352.268,41.873H147.661c-15.877,0-28.665,12.788-28.665,28.665v358.924c0,15.877,12.788,28.665,28.665,28.665 h204.607c15.877,0,28.737-12.788,28.737-28.665V70.538C381.005,54.66,368.145,41.873,352.268,41.873z"></path> </g> <g id="object" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:svg="http://www.w3.org/2000/svg"> <g> <path class="st1" d="M147.66,41.873h204.607c15.877,0,28.737,12.788,28.737,28.665v358.924c0,15.877-12.86,28.665-28.737,28.665 H147.66c-15.877,0-28.665-12.788-28.665-28.665V70.538C118.995,54.66,131.783,41.873,147.66,41.873L147.66,41.873z"></path> <path class="st2" d="M154.629,79.23h190.67c12.572,0,22.702,10.13,22.702,22.702v284.568c0,12.644-10.13,22.774-22.702,22.774 h-190.67c-12.572,0-22.702-10.13-22.702-22.774V101.933C131.927,89.36,142.057,79.23,154.629,79.23L154.629,79.23z"></path> <path class="st3" d="M342.569,64.719c0,3.52-2.946,6.322-6.466,6.322c-3.592,0-6.538-2.802-6.538-6.322 c0-3.449,2.946-6.251,6.538-6.251C339.623,58.468,342.569,61.27,342.569,64.719L342.569,64.719z"></path> <path class="st3" d="M234.877,56.528h26.294c3.305,0,5.963,2.658,5.963,5.963c0,3.305-2.658,5.963-5.963,5.963h-26.294 c-3.305,0-5.963-2.658-5.963-5.963C228.914,59.187,231.572,56.528,234.877,56.528L234.877,56.528z"></path> <path class="st3" d="M181.067,59.833c0,2.011-1.509,3.735-3.448,3.735c-1.868,0-3.448-1.724-3.448-3.735 c0-2.084,1.581-3.736,3.448-3.736C179.559,56.097,181.067,57.75,181.067,59.833L181.067,59.833z"></path> <path class="st4" d="M156.282,79.23c-12.788,0-23.205,9.052-24.283,20.763l236.002,272.714V101.933 c0-12.572-10.848-22.702-24.355-22.702H156.282z"></path> <path class="st3" d="M262.249,430.755c0,6.466-5.388,11.639-12.07,11.639c-6.609,0-11.998-5.173-11.998-11.639 c0-6.394,5.388-11.638,11.998-11.638C256.861,419.117,262.249,424.362,262.249,430.755L262.249,430.755z"></path> </g> </g> </g>
                                </svg>
                                <p class="text-gray-900">{{ $location->phone }}</p>
                            </div>
                            @endif

                            @if($location->email)
                            <div class="flex items-start gap-1">
                                <svg class="size-5" fill="#000000" viewBox="0 0 24 24" id="email" data-name="Flat Line" xmlns="http://www.w3.org/2000/svg" class="icon flat-line">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path id="secondary" d="M20.61,5.23l-8,6.28a1,1,0,0,1-1.24,0l-8-6.28A1,1,0,0,0,3,6V18a1,1,0,0,0,1,1H20a1,1,0,0,0,1-1V6A1,1,0,0,0,20.61,5.23Z" style="fill: #2ca9bc; stroke-width: 2;"></path><path id="primary" d="M20,19H4a1,1,0,0,1-1-1V6A1,1,0,0,1,4,5H20a1,1,0,0,1,1,1V18A1,1,0,0,1,20,19ZM20,5H4a1,1,0,0,0-.62.22l8,6.29a1,1,0,0,0,1.24,0l8-6.29A1,1,0,0,0,20,5Z" style="fill: none; stroke: #000000; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path></g>
                                </svg>
                                <p class="text-gray-900">{{ $location->email }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- QR Scanner Modal -->
                    <div 
                        id="qr-scanner-modal" 
                        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center"
                        style="display: none;"
                    >
                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Scan Member QR Code</h3>
                                <button 
                                    type="button"
                                    onclick="closeQrScanner()"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <div class="rounded-xl overflow-hidden border border-gray-200 bg-black">
                                    <video id="qr-scanner-video" class="w-full h-64 object-cover" playsinline autoplay></video>
                                </div>
                                <div id="qr-scanner-error" class="mt-3 text-sm text-red-600 hidden"></div>
                                <div id="qr-scanner-loading" class="mt-3 text-sm text-gray-600 hidden">Initializing camera...</div>
                                
                                <!-- Scan Response Alert -->
                                <div id="qr-scan-response" class="mt-3 hidden">
                                    <div id="qr-scan-response-content" class="rounded-lg p-3 text-sm font-semibold"></div>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button 
                                    type="button"
                                    onclick="restartQrScanner()"
                                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-all"
                                >
                                    Restart Scanner
                                </button>
                                <button 
                                    type="button"
                                    onclick="closeQrScanner()"
                                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Location code for API calls
                        const LOCATION_CODE = '{{ $location->location_code }}';
                        
                        // QR Scanner state
                        let qrScannerState = {
                            stream: null,
                            detector: null,
                            scanTimer: null,
                            isScanning: false
                        };


                        function openQrScanner() {
                            const modal = document.getElementById('qr-scanner-modal');
                            if (!modal) return;
                            
                            // Hide any previous response
                            const responseDiv = document.getElementById('qr-scan-response');
                            if (responseDiv) {
                                responseDiv.classList.add('hidden');
                            }
                            
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            modal.style.display = 'flex';
                            logQrScan('QR Scanner opened', { location_code: LOCATION_CODE });
                            startQrScanner();
                        }

                        function closeQrScanner() {
                            stopQrScanner();
                            const modal = document.getElementById('qr-scanner-modal');
                            if (modal) {
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                                modal.style.display = 'none';
                            }
                            logQrScan('QR Scanner closed');
                        }

                        async function startQrScanner() {
                            const video = document.getElementById('qr-scanner-video');
                            const errorDiv = document.getElementById('qr-scanner-error');
                            const loadingDiv = document.getElementById('qr-scanner-loading');
                            
                            if (!video) return;

                            // Reset UI
                            if (errorDiv) {
                                errorDiv.classList.add('hidden');
                                errorDiv.textContent = '';
                            }
                            if (loadingDiv) {
                                loadingDiv.classList.remove('hidden');
                            }

                            // Check camera support
                            if (!navigator.mediaDevices?.getUserMedia) {
                                showError('Camera is not supported on this device/browser.');
                                logQrScan('Camera not supported', { error: 'getUserMedia not available' });
                                if (loadingDiv) loadingDiv.classList.add('hidden');
                                return;
                            }

                            try {
                                // Request camera access
                                qrScannerState.stream = await navigator.mediaDevices.getUserMedia({
                                    video: { facingMode: { ideal: 'environment' } },
                                    audio: false
                                });
                                
                                video.srcObject = qrScannerState.stream;
                                await video.play();
                                
                                logQrScan('Camera access granted');
                            } catch (e) {
                                showError('Camera permission denied or camera not available.');
                                logQrScan('Camera access denied', { error: e.message });
                                if (loadingDiv) loadingDiv.classList.add('hidden');
                                return;
                            }

                            // Check BarcodeDetector support
                            if (!('BarcodeDetector' in window)) {
                                showError('QR scan is not supported on this browser. Please use Chrome on Android, or update your browser.');
                                logQrScan('BarcodeDetector not supported');
                                if (loadingDiv) loadingDiv.classList.add('hidden');
                                stopQrScanner();
                                return;
                            }

                            try {
                                qrScannerState.detector = new BarcodeDetector({ formats: ['qr_code'] });
                                logQrScan('BarcodeDetector initialized');
                            } catch (e) {
                                showError('Failed to initialize QR scanner.');
                                logQrScan('BarcodeDetector initialization failed', { error: e.message });
                                if (loadingDiv) loadingDiv.classList.add('hidden');
                                stopQrScanner();
                                return;
                            }

                            if (loadingDiv) loadingDiv.classList.add('hidden');
                            qrScannerState.isScanning = true;

                            // Start polling for QR codes
                            qrScannerState.scanTimer = setInterval(async () => {
                                if (!qrScannerState.detector || !video || !qrScannerState.isScanning) return;
                                
                                try {
                                    const codes = await qrScannerState.detector.detect(video);
                                    if (codes && codes.length > 0) {
                                        const scannedValue = codes[0].rawValue;
                                        logQrScan('QR Code detected', { member_fin: scannedValue });
                                        await handleQrScan(scannedValue);
                                    }
                                } catch (e) {
                                    // Ignore transient detection errors
                                }
                            }, 300);
                        }

                        function stopQrScanner() {
                            qrScannerState.isScanning = false;
                            
                            if (qrScannerState.scanTimer) {
                                clearInterval(qrScannerState.scanTimer);
                                qrScannerState.scanTimer = null;
                            }

                            if (qrScannerState.stream) {
                                qrScannerState.stream.getTracks().forEach(track => track.stop());
                                qrScannerState.stream = null;
                            }

                            const video = document.getElementById('qr-scanner-video');
                            if (video) {
                                video.srcObject = null;
                            }

                            qrScannerState.detector = null;
                        }

                        function resumeQrScanning() {
                            const video = document.getElementById('qr-scanner-video');
                            if (!video || !qrScannerState.stream) {
                                // If stream is lost, restart completely
                                logQrScan('Stream lost, restarting scanner');
                                stopQrScanner();
                                setTimeout(() => startQrScanner(), 100);
                                return;
                            }
                            
                            // Stream is still active, just restart detection
                            logQrScan('Resuming QR scanning');
                            
                            // Clear any existing timer
                            if (qrScannerState.scanTimer) {
                                clearInterval(qrScannerState.scanTimer);
                                qrScannerState.scanTimer = null;
                            }
                            
                            // Reinitialize detector if needed
                            if (!qrScannerState.detector && 'BarcodeDetector' in window) {
                                try {
                                    qrScannerState.detector = new BarcodeDetector({ formats: ['qr_code'] });
                                } catch (e) {
                                    logQrScan('Failed to reinitialize detector', { error: e.message });
                                    return;
                                }
                            }
                            
                            qrScannerState.isScanning = true;
                            
                            // Start polling for QR codes
                            qrScannerState.scanTimer = setInterval(async () => {
                                if (!qrScannerState.detector || !video || !qrScannerState.isScanning) return;
                                
                                try {
                                    const codes = await qrScannerState.detector.detect(video);
                                    if (codes && codes.length > 0) {
                                        const scannedValue = codes[0].rawValue;
                                        logQrScan('QR Code detected', { member_fin: scannedValue });
                                        await handleQrScan(scannedValue);
                                    }
                                } catch (e) {
                                    // Ignore transient detection errors
                                }
                            }, 300);
                        }

                        function restartQrScanner() {
                            logQrScan('Restarting QR scanner');
                            stopQrScanner();
                            setTimeout(() => startQrScanner(), 100);
                        }

                        function showError(message) {
                            const errorDiv = document.getElementById('qr-scanner-error');
                            if (errorDiv) {
                                errorDiv.textContent = message;
                                errorDiv.classList.remove('hidden');
                            }
                        }

                        async function handleQrScan(memberFin) {
                            if (!memberFin || !memberFin.trim()) {
                                logQrScan('Invalid QR code scanned', { member_fin: memberFin });
                                showToast('error', 'Invalid QR code. Please try again.');
                                return;
                            }

                            // Stop scanning to prevent multiple scans
                            stopQrScanner();

                            const loadingDiv = document.getElementById('qr-scanner-loading');
                            if (loadingDiv) {
                                loadingDiv.textContent = 'Processing scan...';
                                loadingDiv.classList.remove('hidden');
                            }

                            try {
                                logQrScan('Sending scan request to API', {
                                    member_fin: memberFin,
                                    location_code: LOCATION_CODE,
                                    type_of_activity: 'ENTRY'
                                });

                                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                const apiUrl = '{{ route("api.member-activity.scan") }}';
                                
                                logQrScan('API URL', { url: apiUrl });
                                
                                const response = await fetch(apiUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
                                    },
                                    body: JSON.stringify({
                                        member_fin: memberFin.trim(),
                                        location_code: LOCATION_CODE,
                                        type_of_activity: 'ENTRY'
                                    })
                                });

                                const data = await response.json();

                                if (loadingDiv) loadingDiv.classList.add('hidden');

                                if (response.ok && data.success) {
                                    logQrScan('Scan successful', {
                                        member_fin: memberFin,
                                        location_code: LOCATION_CODE,
                                        response: data
                                    });
                                    
                                    const message = data.data?.points_awarded 
                                        ? `Member ${data.data.member.name} scanned successfully! ${data.data.points_awarded} points awarded.`
                                        : `Member ${data.data.member.name} scanned successfully!`;
                                    
                                    showToast('success', message);
                                    showScanResponse('success', message);
                                    
                                    // Dispatch event to update points header in real-time
                                    if (data.data?.points_awarded) {
                                        window.dispatchEvent(new CustomEvent('points-updated'));
                                    }
                                    
                                    // Resume scanning after showing response (only restart detection, keep camera)
                                    setTimeout(() => {
                                        resumeQrScanning();
                                    }, 2000);
                                } else {
                                    const errorMessage = data.message || 'Failed to record activity';
                                    logQrScan('Scan failed', {
                                        member_fin: memberFin,
                                        location_code: LOCATION_CODE,
                                        error: errorMessage,
                                        response: data
                                    });
                                    showToast('error', errorMessage);
                                    showScanResponse('error', errorMessage);
                                    
                                    // Resume scanning after showing response (only restart detection, keep camera)
                                    setTimeout(() => {
                                        resumeQrScanning();
                                    }, 2000);
                                }
                            } catch (error) {
                                if (loadingDiv) loadingDiv.classList.add('hidden');
                                
                                logQrScan('Scan error', {
                                    member_fin: memberFin,
                                    location_code: LOCATION_CODE,
                                    error: error.message,
                                    stack: error.stack
                                });
                                
                                const errorMessage = 'Network error. Please check your connection and try again.';
                                showToast('error', errorMessage);
                                showScanResponse('error', errorMessage);
                                
                                // Resume scanning after showing response (only restart detection, keep camera)
                                setTimeout(() => {
                                    resumeQrScanning();
                                }, 2000);
                            }
                        }

                        function showScanResponse(type, message) {
                            const responseDiv = document.getElementById('qr-scan-response');
                            const responseContent = document.getElementById('qr-scan-response-content');
                            
                            if (!responseDiv || !responseContent) return;
                            
                            // Clear previous classes
                            responseContent.className = 'rounded-lg p-3 text-sm font-semibold';
                            
                            // Add appropriate styling based on type
                            if (type === 'success') {
                                responseContent.classList.add('bg-green-100', 'border', 'border-green-400', 'text-green-800');
                            } else {
                                responseContent.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-800');
                            }
                            
                            responseContent.textContent = message;
                            responseDiv.classList.remove('hidden');
                            
                            // Auto-hide after 5 seconds
                            setTimeout(() => {
                                responseDiv.classList.add('hidden');
                            }, 5000);
                        }

                        function showToast(type, message) {
                            console.log('[QR Scanner] Dispatching toast:', { type, message });
                            
                            // Use requestAnimationFrame to ensure DOM is ready
                            requestAnimationFrame(() => {
                                // Dispatch the custom event for Alpine.js to catch
                                const event = new CustomEvent('hv-toast', {
                                    detail: { type, message },
                                    bubbles: true,
                                    cancelable: true
                                });
                                
                                // Dispatch on window (Alpine listens here)
                                window.dispatchEvent(event);
                                
                                console.log('[QR Scanner] Toast event dispatched to window');
                            });
                        }

                        function logQrScan(action, data = {}) {
                            const logData = {
                                action,
                                timestamp: new Date().toISOString(),
                                location_code: LOCATION_CODE,
                                ...data
                            };
                            console.log('[QR Scanner]', logData);
                        }

                        // Cleanup on page unload
                        window.addEventListener('beforeunload', () => {
                            stopQrScanner();
                        });
                    </script>
                </div>
            </div>
            <!-- Recent Events Card -->
            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-4 sm:p-6">
                <div class="flex lg:flex-row flex-col lg:justify-between justify-center items-center mb-4 border-b pb-2">
                    <h3 class="lg:text-lg text-2xl font-semibold text-gray-800">Recent Events</h3>
                    <div class="flex gap-2 items-center">
                        {{-- add the Create Event button here --}}
                        <a href="{{ route('admin.locations.events.create', $location->location_code) }}" class="lg:text-sm text-xs underline text-orange-600 font-semibold transition-all duration-200">
                            Create New Event
                        </a>
                        <span class="text-gray-400">|</span>
                        <a href="{{ route('admin.locations.events.index', $location->location_code) }}" class="lg:text-sm text-xs font-semibold underline text-orange-500 hover:text-orange-600">
                            View All
                        </a>
                    </div>
                </div>
                
                @if($location->events->count() > 0)
                    <div class="space-y-3">
                        @foreach($location->events as $event)
                            <a
                                href="{{ route('admin.events.profile', $event->event_code) }}"
                                class="flex flex-col sm:flex-row gap-3 border-l-4 border-orange-500 pl-3 py-3 pr-2.5 sm:py-4 sm:pr-3 group bg-gray-50 hover:bg-orange-50 sm:hover:-translate-y-0.5 shadow-sm hover:shadow-md transition-all duration-300"
                            >
                                <div class="h-32 w-full sm:h-16 sm:w-16 rounded-lg bg-white border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if ($event->thumbnail_url)
                                        <img src="{{ $event->thumbnail_url }}" alt="" class="w-full h-full rounded-lg object-cover">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 sm:size-6 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1.5 sm:gap-2">
                                        <h4 class="text-base sm:text-lg font-semibold text-orange-500 group-hover:text-orange-600 transition-colors duration-300 break-words">
                                            {{ $event->title }}
                                        </h4>
                                        <span class="self-start shrink-0 inline-flex text-xs leading-5 font-semibold rounded-full px-2.5 py-0.5 {{ $event->statusBadgeClasses() }}">
                                            {{ $event->displayStatus() }}
                                        </span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex text-xs leading-5 font-semibold rounded-full px-2 py-0.5 {{ $event->scheduleBadgeClasses() }}">
                                            {{ $event->scheduleLabel() }}
                                        </span>
                                        @if ($event->isAcceptingRegistrations())
                                            <span class="inline-flex text-xs leading-5 font-semibold rounded-full px-2 py-0.5 border border-yellow-600 bg-yellow-100 text-yellow-800">
                                                {{ __('Accepting registrations') }}
                                            </span>
                                        @elseif ($event->status === 'published' && $event->isFull() && ! $event->hasEnded())
                                            <span class="inline-flex text-xs leading-5 font-semibold rounded-full px-2 py-0.5 border border-red-500 bg-red-100 text-red-800">
                                                {{ __('Full') }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-gray-600 mt-2 break-words leading-relaxed">{{ $event->formattedSchedule() }}</p>

                                    @if ($event->venue)
                                        <p class="text-xs text-gray-600 mt-0.5 break-words">{{ __('Venue') }}: <span class="font-medium text-gray-800">{{ $event->venue }}</span></p>
                                    @endif

                                    <div class="text-xs text-gray-600 mt-1.5 sm:mt-0.5 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-0.5 sm:gap-x-2">
                                        <p>
                                            {{ __('Registrations') }}:
                                            <span class="font-semibold text-orange-600">
                                                {{ number_format($event->registrations_count) }}
                                                @if ($event->max_participants)
                                                    / {{ number_format($event->max_participants) }}
                                                @endif
                                            </span>
                                        </p>
                                        <span class="hidden sm:inline text-gray-400">·</span>
                                        <p>
                                            {{ __('Attended') }}:
                                            <span class="font-semibold text-gray-800">{{ number_format($event->attended_count) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 flex items-center justify-center gap-2 py-4">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M3 9H21M7 3V5M17 3V5M6 12H8M11 12H13M16 12H18M6 15H8M11 15H13M16 15H18M6 18H8M11 18H13M16 18H18M6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z" stroke="#cfcfcf" stroke-width="2" stroke-linecap="round"></path> </g></svg>
                        <span class="text-gray-500 text-sm">No events yet</span>
                    </p>
                @endif
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
