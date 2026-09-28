<div>
    @php
        $requiredPermission = $locationId ? 'location.edit' : 'location.create';
    @endphp

    @if (Illuminate\Support\Facades\Gate::allows($requiredPermission))
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $locationId ? __('Edit Location') : __('Add New Location') }}
                    </h2>
                    <a href="{{ route('admin.locations.index') }}" class="text-orange-600 hover:text-orange-800 transition-all duration-200">
                        ← Back to Locations
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
                @if (session()->has('message'))
                    <div 
                        x-data="{ 
                            show: @entangle('showMessage').live,
                            timeoutId: null
                        }"
                        x-init="
                            $watch('show', value => {
                                if (value && !timeoutId) {
                                    timeoutId = setTimeout(() => {
                                        show = false;
                                        timeoutId = null;
                                    }, 3000);
                                } else if (!value && timeoutId) {
                                    clearTimeout(timeoutId);
                                    timeoutId = null;
                                }
                            });
                            if (show) {
                                timeoutId = setTimeout(() => {
                                    show = false;
                                    timeoutId = null;
                                }, 3000);
                            }
                        "
                        x-show="show"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-out duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                        role="alert"
                    >
                        <span class="block sm:inline">{{ session('message') }}</span>
                    </div>
                @endif

                {{-- <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"> --}}
                    <form wire:submit="save">
                        <div class="flex gap-4 lg:flex-row flex-col">
                            {{-- left side --}}
                            <div class="lg:w-1/3 w-full">
                                <!-- Thumbnail Upload -->
                                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Thumbnail</label>
                                    
                                    @if($existingThumbnail && !$thumbnail)
                                        <div class="mb-4 relative">
                                            <img src="{{ $existingThumbnail }}" alt="Current thumbnail" class="w-full h-64 mb-4 object-cover rounded-lg border border-gray-300">
                                            <button 
                                                type="button" 
                                                wire:click="removeThumbnail" 
                                                title="Remove Thumbnail"
                                                wire:confirm="Are you sure you want to remove the thumbnail?"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-not-allowed"
                                                wire:loading.class.remove="opacity-100 cursor-pointer"
                                                class="flex gap-1 items-center justify-center absolute -top-1.5 -right-2 hover:scale-105 transition-all duration-300 text-red-600 hover:text-red-800 text-xs border border-red-300 bg-red-100 hover:bg-red-200/75 p-1 rounded-full"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif

                                    @if($thumbnail)
                                        <div class="mt-2">
                                            <img src="{{ $thumbnail->temporaryUrl() }}" alt="Preview" class="w-full h-64 mb-4 object-cover rounded-lg border border-gray-300">
                                        </div>
                                    @endif
                                    @if(!$thumbnail && !$existingThumbnail)
                                    <div class="w-full h-64 mb-4 bg-gray-200 rounded-lg flex flex-col items-center justify-center border border-gray-300">
                                            <p class="text-gray-400 text-sm">IMG</p>
                                            <p class="text-gray-400 text-sm">Upload</p>
                                        </div>
                                    @endif

                                    <input 
                                        type="file" 
                                        wire:model="thumbnail" 
                                        accept="image/jpeg,image/png,image/webp"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold bg-gray-200/50 border border-gray-300 rounded-lg file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-100"
                                    >
                                    @error('thumbnail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="lg:w-2/3 w-full">
                                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                                    <!-- Name -->
                                    <div class="mb-4">
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                        <input 
                                            placeholder="Location Name"
                                            type="text" 
                                            id="name"
                                            wire:model.blur="name" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('name') border-red-500 @enderror"
                                        >
                                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
            
                                    <!-- Description -->
                                    <div class="mb-4">
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <textarea 
                                            placeholder="Description"
                                            id="description"
                                            wire:model.blur="description" 
                                            rows="4"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('description') border-red-500 @enderror"
                                        ></textarea>
                                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <h3 class="text-lg font-bold text-gray-700 mb-2 border-b border-gray-300 pb-2">Contact Information</h3>

                                    <!-- Phone -->
                                    <div class="mb-4">
                                        <div>
                                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                            <input 
                                                placeholder="Phone"
                                                type="text" 
                                                id="phone"
                                                wire:model.blur="phone" 
                                                class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('phone') border-red-500 @enderror"
                                            >
                                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
            
                                    <!-- Email -->
                                    <div class="mb-4">
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input 
                                            placeholder="Email"
                                            type="email" 
                                            id="email"
                                            wire:model.blur="email" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('email') border-red-500 @enderror"
                                        >
                                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <h3 class="mt-8 text-lg font-bold text-gray-700 mb-2 border-b border-gray-300 pb-2">Location Address</h3>

                                    <!-- Address -->
                                    <div class="mt-4 mb-4">
                                        <input 
                                            placeholder="Search for an address or place here..."
                                            type="text" 
                                            id="address"
                                            wire:model.blur="address" 
                                            class="w-full px-4 py-2 border border-indigo-200 rounded-full bg-indigo-50 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700"
                                        >
                                        <p class="mt-1 text-xs italic text-gray-400">
                                            Start typing to search for an address. The map will update automatically. 
                                            Or click on the map to pin the location. Address fields will be automatically populated.
                                        </p>
                                        @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Map Navigation -->
                                    <div class="mb-4">
                                        <div wire:ignore id="map" class="w-full h-64 rounded-lg border border-gray-300" style="min-height: 350px;">
                                            <div class="flex items-center justify-center h-full text-gray-400">
                                                <div class="text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                    </svg>
                                                    <p class="mt-2 text-sm">Loading map...</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="map-error" class="hidden mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                            <p class="text-sm text-yellow-800">
                                                <strong>Map blocked:</strong> Google Maps appears to be blocked by your browser or an extension (like an ad blocker). 
                                                Please disable ad blockers for this site or manually enter the address fields below.
                                            </p>
                                        </div>
                                        <div class="mt-2 flex gap-2">
                                            <button 
                                                type="button" 
                                                id="use-current-location"
                                                class="px-4 py-2 text-sm bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                disabled
                                            >
                                                Use Current Location
                                            </button>
                                            {{-- <button 
                                                type="button" 
                                                id="clear-map"
                                                class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                disabled
                                            >
                                                Clear Map
                                            </button> --}}
                                        </div>
                                    </div>
            
                                    <div class="mb-4">
                                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                        <input readonly disabled placeholder="Auto-populated Address from the map" type="text" wire:model.blur="address" class="disabled:bg-gray-100 w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700" />
                                    </div>
                                    <!-- City and Province -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                            <input 
                                                placeholder="City"
                                                type="text" 
                                                id="city"
                                                wire:model.blur="city" 
                                                class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('city') border-red-500 @enderror"
                                            >
                                            @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="province" class="block text-sm font-medium text-gray-700 mb-2">Province or State</label>
                                            <input 
                                                placeholder="Province or State"
                                                type="text" 
                                                id="province"
                                                wire:model.blur="province" 
                                                class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('province') border-red-500 @enderror"
                                            >
                                            @error('province') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                            <input 
                                                placeholder="Postal Code"
                                                type="text" 
                                                id="postal_code"
                                                wire:model.blur="postal_code" 
                                                class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('postal_code') border-red-500 @enderror"
                                            >
                                            @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                        
                                    <!-- Is Active -->
                                    <div class="my-6">
                                        <label class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                wire:model.blur="is_active" 
                                                class="w-4 h-4 p-3 rounded-full border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-md text-gray-700">Active</span>
                                        </label>
                                    </div>
            
                                    <!-- Submit Buttons -->
                                    <div class="flex justify-end gap-4">
                                        <a 
                                            href="{{ route('admin.locations.index') }}" 
                                            class="px-6 py-2 border border-gray-300 rounded-full text-gray-700 hover:bg-gray-50 transition duration-300"
                                        >
                                            Cancel
                                        </a>
                                        <button 
                                            type="submit" 
                                            class="px-6 py-2 bg-orange-600 text-white cursor-pointer rounded-full hover:bg-orange-700 transition duration-300"
                                        >
                                            {{ $locationId ? 'Update' : 'Create' }} Location
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                {{-- </div> --}}
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endif
</div>

@push('scripts')
<script>
    let map;
    let marker;
    let geocoder;
    let mapInitialized = false;
    const GOOGLE_MAPS_API_KEY = @json(config('services.google_maps.api_key'));

    // Default center (you can change this to a default location)
    let defaultLat = @js($latitude ?? 1.2833);
    let defaultLng = @js($longitude ?? 103.8608);

    function initMap() {
        if (mapInitialized || !document.getElementById('map')) {
            return;
        }

        // Initialize map
        map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: defaultLat, lng: defaultLng },
            zoom: 10,
            mapTypeControl: true,
            streetViewControl: true,
        });

        geocoder = new google.maps.Geocoder();
        mapInitialized = true;
        
        // Enable buttons now that map is loaded
        enableMapButtons();
        
        // Hide any error messages
        const errorElement = document.getElementById('map-error');
        if (errorElement) {
            errorElement.classList.add('hidden');
        }

        // Clear loading message
        const mapElement = document.getElementById('map');
        if (mapElement && mapElement.querySelector('.text-gray-400')) {
            mapElement.innerHTML = '';
        }

        // Initialize Places Autocomplete for address field (after map is ready)
        setTimeout(() => {
            initAddressAutocomplete();
        }, 100);

        // If editing and we have address, try to geocode it
        @if($locationId && $address)
            setTimeout(() => {
                geocodeAddress(@js($address));
            }, 500);
        @endif

        // Add click listener to map
        map.addListener('click', (event) => {
            placeMarker(event.latLng);
            reverseGeocode(event.latLng);
        });

        // Use Current Location button
        const useCurrentLocationBtn = document.getElementById('use-current-location');
        if (useCurrentLocationBtn) {
            useCurrentLocationBtn.addEventListener('click', () => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const pos = new google.maps.LatLng(
                                position.coords.latitude,
                                position.coords.longitude
                            );
                            placeMarker(pos);
                            reverseGeocode(pos);
                            map.setCenter(pos);
                            map.setZoom(15);
                        },
                        () => {
                            alert('Error: The Geolocation service failed.');
                        }
                    );
                } else {
                    alert('Error: Your browser doesn\'t support geolocation.');
                }
            });
        }

        // Clear Map button
        // const clearMapBtn = document.getElementById('clear-map');
        // if (clearMapBtn) {
        //     clearMapBtn.addEventListener('click', () => {
        //         if (marker) {
        //             marker.setMap(null);
        //             marker = null;
        //         }
        //         @this.set('address', '');
        //         @this.set('city', '');
        //         @this.set('province', '');
        //         @this.set('postal_code', '');
        //         @this.set('latitude', null);
        //         @this.set('longitude', null);
        //         map.setCenter({ lat: defaultLat, lng: defaultLng });
        //     });
        // }
    }

    function placeMarker(location) {
        if (!map || !mapInitialized) {
            console.error('Cannot place marker: map not initialized');
            return;
        }

        // Ensure location is a LatLng object
        if (!(location instanceof google.maps.LatLng)) {
            if (location.lat && location.lng) {
                location = new google.maps.LatLng(location.lat, location.lng);
            } else if (typeof location.lat === 'function') {
                // Already a LatLng object
            } else {
                console.error('Invalid location for marker');
                return;
            }
        }

        if (marker) {
            marker.setPosition(location);
        } else {
            // Using Marker (deprecated but still functional)
            // Note: AdvancedMarkerElement requires additional setup and marker library
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });

            // Add drag listener to update address when marker is dragged
            marker.addListener('dragend', (event) => {
                reverseGeocode(event.latLng);
            });
        }
        
        if (map) {
            map.setCenter(location);
        }
    }

    function reverseGeocode(latLng) {
        if (!geocoder) return;
        
        // Ensure latLng is a google.maps.LatLng object
        if (!(latLng instanceof google.maps.LatLng)) {
            if (latLng.lat && latLng.lng) {
                latLng = new google.maps.LatLng(latLng.lat, latLng.lng);
            } else if (typeof latLng.lat === 'function') {
                // Already a LatLng object
            } else {
                console.error('Invalid latLng object');
                return;
            }
        }
        
        geocoder.geocode({ location: latLng }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const addressComponents = parseAddressComponents(results[0].address_components);
                
                @this.call('updateAddressFromMap', {
                    address: results[0].formatted_address || addressComponents.street,
                    city: addressComponents.city,
                    province: addressComponents.province,
                    postal_code: addressComponents.postal_code,
                    latitude: latLng.lat(),
                    longitude: latLng.lng()
                });
            } else {
                console.error('Geocoder failed due to: ' + status);
            }
        });
    }

    function geocodeAddress(address) {
        if (!geocoder || !address) return;
        
        // Get country restriction from Livewire (default to Singapore)
        const countryCode = @js($country ?? 'SG');
        
        geocoder.geocode({ 
            address: address,
            componentRestrictions: { country: countryCode }
        }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;
                placeMarker(location);
                map.setCenter(location);
                map.setZoom(15);
            }
        });
    }

    function initAddressAutocomplete() {
        const addressInput = document.getElementById('address');
        if (!addressInput || typeof google.maps.places === 'undefined') {
            return;
        }

        // Ensure map is initialized before setting up autocomplete
        if (!map || !mapInitialized) {
            console.warn('Map not initialized yet, retrying autocomplete setup...');
            setTimeout(initAddressAutocomplete, 200);
            return;
        }

        // Get country restriction from Livewire (default to Singapore)
        const countryCode = @js($country ?? 'SG');

        // Create autocomplete instance with country restriction
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['address'],
            fields: ['formatted_address', 'geometry', 'address_components'],
            componentRestrictions: { country: countryCode }
        });

        // When a place is selected
        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                console.warn('No details available for input: ' + place.name);
                return;
            }

            // Ensure map is available
            if (!map || !mapInitialized) {
                console.error('Map not available when place was selected');
                return;
            }

            // Update map to show the selected place
            const location = place.geometry.location;
            if (location) {
                placeMarker(location);
                map.setCenter(location);
                map.setZoom(15);

                // Parse address components and update fields
                const addressComponents = parseAddressComponents(place.address_components);
                
                // Use setTimeout to ensure map operations complete before Livewire update
                setTimeout(() => {
                    @this.call('updateAddressFromMap', {
                        address: place.formatted_address || addressComponents.street,
                        city: addressComponents.city,
                        province: addressComponents.province,
                        postal_code: addressComponents.postal_code,
                        latitude: location.lat(),
                        longitude: location.lng()
                    });
                }, 100);
            }
        });

        // Also handle manual input changes (debounced)
        let geocodeTimeout;
        addressInput.addEventListener('input', (e) => {
            clearTimeout(geocodeTimeout);
            
            // Only geocode if the input doesn't match autocomplete selection
            // and has a reasonable length
            if (e.target.value.length > 5) {
                geocodeTimeout = setTimeout(() => {
                    // Check if this is a manual input (not from autocomplete)
                    if (e.target.value && !autocomplete.getPlace()) {
                        geocodeAddress(e.target.value);
                    }
                }, 1000); // Wait 1 second after user stops typing
            }
        });
    }

    function parseAddressComponents(components) {
        const addressData = {
            street: '',
            city: '',
            province: '',
            postal_code: ''
        };

        components.forEach(component => {
            const types = component.types;
            
            if (types.includes('street_number')) {
                addressData.street = component.long_name + ' ' + addressData.street;
            }
            if (types.includes('route')) {
                addressData.street += component.long_name;
            }
            if (types.includes('locality') || types.includes('administrative_area_level_2')) {
                addressData.city = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                addressData.province = component.short_name;
            }
            if (types.includes('postal_code')) {
                addressData.postal_code = component.long_name;
            }
        });

        return addressData;
    }

    function showMapError(message) {
        const mapElement = document.getElementById('map');
        const errorElement = document.getElementById('map-error');
        const useLocationBtn = document.getElementById('use-current-location');
        // const clearMapBtn = document.getElementById('clear-map');
        
        if (mapElement) {
            const escapedMessage = message.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            mapElement.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="mt-2 text-sm">${escapedMessage}</p>
                    </div>
                </div>
            `;
        }
        
        if (errorElement) {
            errorElement.classList.remove('hidden');
        }
        
        if (useLocationBtn) useLocationBtn.disabled = true;
        if (clearMapBtn) clearMapBtn.disabled = true;
    }

    function enableMapButtons() {
        const useLocationBtn = document.getElementById('use-current-location');
        // const clearMapBtn = document.getElementById('clear-map');
        if (useLocationBtn) useLocationBtn.disabled = false;
        // if (clearMapBtn) clearMapBtn.disabled = false;
    }

    // Load Google Maps script
    function loadGoogleMaps() {
        if (!GOOGLE_MAPS_API_KEY) {
            showMapError('Google Maps API key is not configured. Please set GOOGLE_MAPS_API_KEY in your .env file.');
            return;
        }

        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            window.initMap = initMap;
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places&loading=async&callback=initMap`;
            script.async = true;
            script.defer = true;
            
            // Handle script loading errors
            script.onerror = function() {
                showMapError('Failed to load Google Maps. It may be blocked by your browser or an extension.');
                console.error('Google Maps script failed to load');
            };
            
            // Set a timeout to detect if the script doesn't load
            const loadTimeout = setTimeout(() => {
                if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                    showMapError('Google Maps failed to load. It may be blocked by your browser or an extension.');
                    console.warn('Google Maps did not load within timeout period');
                }
            }, 10000); // 10 second timeout
            
            // Clear timeout if script loads successfully
            const originalInitMap = window.initMap;
            window.initMap = function() {
                clearTimeout(loadTimeout);
                if (originalInitMap) {
                    originalInitMap();
                } else {
                    initMap();
                }
            };
            
            document.head.appendChild(script);
        } else {
            initMap();
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleMaps);
    } else {
        loadGoogleMaps();
    }

    // Re-initialize map when Livewire loads
    document.addEventListener('livewire:load', () => {
        if (!mapInitialized) {
            setTimeout(loadGoogleMaps, 100);
        }
    });

    // Preserve map during Livewire updates
    document.addEventListener('livewire:before-update', () => {
        // Store map state before update
        if (map && marker) {
            window._mapState = {
                center: map.getCenter(),
                zoom: map.getZoom(),
                markerPosition: marker.getPosition()
            };
        }
    });

    document.addEventListener('livewire:update', () => {
        // Restore map state after update if needed
        if (mapInitialized && map && window._mapState) {
            // Map should persist due to wire:ignore, but ensure it's still there
            const mapElement = document.getElementById('map');
            if (mapElement && !mapElement.querySelector('.gm-style')) {
                // Map was cleared, re-initialize
                console.warn('Map was cleared, re-initializing...');
                mapInitialized = false;
                setTimeout(loadGoogleMaps, 100);
            } else if (map && window._mapState) {
                // Restore position if needed
                if (window._mapState.center) {
                    map.setCenter(window._mapState.center);
                    map.setZoom(window._mapState.zoom || 15);
                }
                if (window._mapState.markerPosition && marker) {
                    marker.setPosition(window._mapState.markerPosition);
                }
                delete window._mapState;
            }
        }
    });
</script>
@endpush
