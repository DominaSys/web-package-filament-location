const googleLoaders = new Map()

function loadGoogleMaps(apiKey, language, region) {
    const loaderKey = `${apiKey}:${language}:${region}`

    if (googleLoaders.has(loaderKey)) {
        return googleLoaders.get(loaderKey)
    }

    const loader = new Promise((resolve, reject) => {
        if (window.google?.maps?.importLibrary) {
            resolve(window.google.maps)

            return
        }

        const callback = `filamentLocationGoogleMapsLoaded${Date.now()}`
        const script = document.createElement('script')

        window[callback] = () => {
            delete window[callback]
            resolve(window.google.maps)
        }

        script.async = true
        script.defer = true
        script.onerror = () => reject(new Error('Não foi possível carregar o Google Maps.'))
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&loading=async&callback=${callback}&language=${encodeURIComponent(language)}&region=${encodeURIComponent(region)}`
        document.head.appendChild(script)
    })

    googleLoaders.set(loaderKey, loader)

    return loader
}

function componentValue(components, type, short = false) {
    const component = components.find((item) => item.types?.includes(type))

    if (!component) {
        return null
    }

    return short
        ? component.shortText ?? component.short_name ?? component.longText ?? component.long_name ?? null
        : component.longText ?? component.long_name ?? component.shortText ?? component.short_name ?? null
}

function raw(value) {
    return window.Alpine?.raw ? window.Alpine.raw(value) : value
}

function normalizePlace(place, predictionText, metadata) {
    const components = place.addressComponents ?? []
    const displayName = place.displayName?.text ?? place.displayName ?? null

    return {
        place_id: place.id ?? null,
        name: predictionText || displayName,
        formatted_address: place.formattedAddress ?? null,
        latitude: place.location?.lat() ?? null,
        longitude: place.location?.lng() ?? null,
        postal_code: componentValue(components, 'postal_code'),
        country: componentValue(components, 'country'),
        country_code: componentValue(components, 'country', true),
        state: componentValue(components, 'administrative_area_level_1'),
        state_code: componentValue(components, 'administrative_area_level_1', true),
        city: componentValue(components, 'administrative_area_level_2')
            ?? componentValue(components, 'locality')
            ?? componentValue(components, 'postal_town'),
        city_code: null,
        neighborhood: componentValue(components, 'sublocality_level_1')
            ?? componentValue(components, 'neighborhood'),
        street: componentValue(components, 'route'),
        number: componentValue(components, 'street_number'),
        source: metadata.place_source,
        precision: metadata.place_precision,
    }
}

function normalizeGeocodedLocation(result, latitude, longitude, metadata) {
    const components = result.address_components ?? []

    return {
        place_id: result.place_id ?? null,
        name: null,
        formatted_address: result.formatted_address ?? null,
        latitude,
        longitude,
        postal_code: componentValue(components, 'postal_code'),
        country: componentValue(components, 'country'),
        country_code: componentValue(components, 'country', true),
        state: componentValue(components, 'administrative_area_level_1'),
        state_code: componentValue(components, 'administrative_area_level_1', true),
        city: componentValue(components, 'administrative_area_level_2')
            ?? componentValue(components, 'locality')
            ?? componentValue(components, 'postal_town'),
        city_code: null,
        neighborhood: componentValue(components, 'sublocality_level_1')
            ?? componentValue(components, 'neighborhood'),
        street: componentValue(components, 'route'),
        number: componentValue(components, 'street_number'),
        source: metadata.pin_source,
        precision: metadata.pin_precision,
    }
}

export default function googlePlacePicker({
    apiKey,
    language,
    region,
    mapId,
    statePath,
    bindings,
    initialLatitude,
    initialLongitude,
    placeholder,
    zoom,
    metadata,
}) {
    const predictionsByPlaceId = new Map()

    return {
        error: null,
        geocoder: null,
        map: null,
        marker: null,
        placeholder,
        query: '',
        suggestions: [],
        isSearching: false,
        isSelecting: false,
        hasSearched: false,
        autocompleteSuggestion: null,
        autocompleteSessionToken: null,
        autocompleteSessionTokenClass: null,
        latestAutocompleteRequest: 0,
        initialized: false,
        visibilityObserver: null,
        intersectionObserver: null,

        init() {
            this.waitUntilVisible()
        },

        destroy() {
            this.visibilityObserver?.disconnect()
            this.intersectionObserver?.disconnect()
        },

        waitUntilVisible() {
            const initializeWhenVisible = () => {
                const rect = this.$root.getBoundingClientRect()
                const isVisible = this.$root.offsetParent !== null && rect.width > 0 && rect.height > 0

                if (!isVisible || this.initialized) {
                    return
                }

                this.initialized = true
                this.visibilityObserver?.disconnect()
                this.initializeGoogle()
            }

            this.visibilityObserver = new ResizeObserver(initializeWhenVisible)
            this.visibilityObserver.observe(this.$root)

            this.intersectionObserver = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    initializeWhenVisible()
                    this.intersectionObserver.disconnect()
                }
            })

            this.intersectionObserver.observe(this.$root)
            this.$nextTick(initializeWhenVisible)
        },

        async initializeGoogle() {
            try {
                if (!mapId) {
                    throw new Error('Configure FILAMENT_LOCATION_GOOGLE_MAP_ID para usar o mapa.')
                }

                const googleMaps = await loadGoogleMaps(apiKey, language, region)
                const [{ Map }, { AdvancedMarkerElement, CollisionBehavior }, { Place, AutocompleteSuggestion, AutocompleteSessionToken }, { Geocoder }] = await Promise.all([
                    googleMaps.importLibrary('maps'),
                    googleMaps.importLibrary('marker'),
                    googleMaps.importLibrary('places'),
                    googleMaps.importLibrary('geocoding'),
                ])
                this.geocoder = new Geocoder()
                this.autocompleteSuggestion = AutocompleteSuggestion
                this.autocompleteSessionTokenClass = AutocompleteSessionToken
                this.refreshAutocompleteSession()

                const latitude = Number(initialLatitude)
                const longitude = Number(initialLongitude)
                const hasCoordinates = Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && !(latitude === 0 && longitude === 0)
                const center = hasCoordinates
                    ? { lat: latitude, lng: longitude }
                    : { lat: -14.235, lng: -51.9253 }

                const map = new Map(this.$refs.map, {
                    center,
                    zoom: hasCoordinates ? zoom : 4,
                    mapId,
                    streetViewControl: false,
                    mapTypeControl: false,
                    clickableIcons: true,
                })
                this.map = map

                const marker = new AdvancedMarkerElement({
                    position: center,
                    collisionBehavior: CollisionBehavior.REQUIRED,
                    gmpDraggable: true,
                    zIndex: 1000,
                    title: 'Arraste o pin ou clique no mapa para ajustar o ponto',
                })
                this.marker = marker

                if (hasCoordinates) {
                    marker.map = map
                }

                marker.addListener('dragend', async () => {
                    const position = marker.position
                    const latitude = typeof position.lat === 'function' ? position.lat() : position.lat
                    const longitude = typeof position.lng === 'function' ? position.lng() : position.lng

                    await this.reverseGeocodeCoordinates(Number(latitude), Number(longitude))
                })

                map.addListener('click', async (event) => {
                    event.stop?.()

                    if (!event.latLng) {
                        return
                    }

                    if (event.placeId) {
                        await this.selectPlace(new Place({ id: event.placeId }))

                        return
                    }

                    marker.map = map
                    marker.position = event.latLng
                    await this.reverseGeocodeCoordinates(event.latLng.lat(), event.latLng.lng())
                })

            } catch (error) {
                console.error('[filament-location] initialization:error', error)
                this.error = error instanceof Error ? error.message : 'Não foi possível inicializar o Google Maps.'
            }
        },

        refreshAutocompleteSession() {
            this.autocompleteSessionToken = this.autocompleteSessionTokenClass
                ? new this.autocompleteSessionTokenClass()
                : null
        },

        async searchPlaces() {
            const input = this.query.trim()
            const requestId = ++this.latestAutocompleteRequest

            if (input.length < 3) {
                this.suggestions = []
                this.isSearching = false
                this.hasSearched = false

                return
            }

            this.isSearching = true
            this.hasSearched = false

            try {
                const { suggestions } = await this.autocompleteSuggestion.fetchAutocompleteSuggestions({
                    input,
                    includedRegionCodes: region ? [region.toLowerCase()] : undefined,
                    language,
                    region,
                    sessionToken: this.autocompleteSessionToken,
                })

                if (requestId !== this.latestAutocompleteRequest) {
                    return
                }

                predictionsByPlaceId.clear()
                this.suggestions = suggestions.flatMap((suggestion) => {
                    const prediction = suggestion.placePrediction

                    if (!prediction) {
                        return []
                    }

                    predictionsByPlaceId.set(prediction.placeId, prediction)

                    return [{
                        placeId: prediction.placeId,
                        mainText: prediction.mainText?.text ?? prediction.text?.text ?? '',
                        secondaryText: prediction.secondaryText?.text ?? '',
                    }]
                })
                this.hasSearched = true
                this.error = null
            } catch (error) {
                if (requestId !== this.latestAutocompleteRequest) {
                    return
                }

                console.error('[filament-location] autocomplete:error', error)
                this.suggestions = []
                this.hasSearched = true
                this.error = 'Não foi possível buscar locais agora. Tente novamente.'
            } finally {
                if (requestId === this.latestAutocompleteRequest) {
                    this.isSearching = false
                }
            }
        },

        async selectSuggestion(suggestion) {
            const prediction = predictionsByPlaceId.get(suggestion?.placeId)

            if (!prediction || this.isSelecting) {
                return
            }

            this.query = suggestion.mainText || this.query
            this.clearSuggestions()
            await this.selectPlace(prediction.toPlace(), suggestion.mainText || null)
            this.refreshAutocompleteSession()
        },

        clearSuggestions() {
            this.latestAutocompleteRequest++
            predictionsByPlaceId.clear()
            this.suggestions = []
            this.isSearching = false
            this.hasSearched = false
        },

        async selectPrediction(prediction) {
            if (!prediction) {
                return
            }

            await this.selectPlace(
                prediction.toPlace(),
                prediction.mainText?.text ?? prediction.text?.text ?? null,
            )
        },

        async selectPlace(place, predictionText = null) {
            const root = this.$root
            this.isSelecting = true

            try {
                await place.fetchFields({
                    fields: ['id', 'displayName', 'formattedAddress', 'location', 'addressComponents'],
                })

                const data = normalizePlace(place, predictionText, metadata)
                await this.syncLocationData(root, data)

                const position = { lat: data.latitude, lng: data.longitude }
                const map = raw(this.map)
                const marker = raw(this.marker)

                marker.map = map
                marker.position = position
                map.setCenter(position)
                map.setZoom(zoom)
                this.error = null
                this.dispatchLocationEvent(root, 'filament-location:place-selected', data)
            } catch (error) {
                console.error('[filament-location] place-selection:error', error)
                this.error = error instanceof Error ? error.message : 'Não foi possível carregar o endereço selecionado.'
            } finally {
                this.isSelecting = false
            }
        },

        syncBoundInputs(data) {
            const livewireRoot = this.$root?.closest('[wire\\:id]')

            if (!livewireRoot) {
                return
            }

            Object.entries(bindings).forEach(([key, path]) => {
                const input = livewireRoot.querySelector(`[wire\\:model="${CSS.escape(path)}"]`)

                if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                    input.value = data[key] ?? ''
                }
            })
        },

        async reverseGeocodeCoordinates(latitude, longitude) {
            const root = this.$root

            try {
                const response = await raw(this.geocoder).geocode({
                    location: { lat: latitude, lng: longitude },
                })
                const result = response.results?.[0]

                if (!result) {
                    throw new Error('Não foi possível encontrar um endereço para o ponto selecionado.')
                }

                const data = normalizeGeocodedLocation(result, latitude, longitude, metadata)
                await this.syncLocationData(root, data)
                this.error = null
                this.dispatchLocationEvent(root, 'filament-location:pin-moved', data)
            } catch (error) {
                console.error('[filament-location] reverse-geocoding:error', error)
                this.error = error instanceof Error ? error.message : 'Não foi possível carregar o endereço selecionado.'
                await this.clearAddressAtCoordinates(root, latitude, longitude)
            }
        },

        async clearAddressAtCoordinates(root, latitude, longitude) {
            const data = normalizeGeocodedLocation({}, latitude, longitude, metadata)

            await this.syncLocationData(root, data)
            this.dispatchLocationEvent(root, 'filament-location:pin-moved', data)
        },

        async syncLocationData(root, data) {
            const livewireId = root?.closest('[wire\\:id]')?.getAttribute('wire:id')
            const wire = livewireId ? window.Livewire.find(livewireId) : null

            if (!wire) {
                throw new Error('Não foi possível sincronizar o endereço com o formulário.')
            }

            for (const [key, path] of Object.entries(bindings)) {
                await wire.$set(path, data[key] ?? null, false)
            }

            if (statePath) {
                await wire.$set(statePath, data, false)
            }

            this.syncBoundInputs(data)
        },

        updateCoordinates(latitude, longitude) {
            const root = this.$root
            const data = {
                latitude,
                longitude,
                source: metadata.pin_source,
                precision: metadata.pin_precision,
            }

            this.$wire.$set(bindings.latitude, latitude, false)
            this.$wire.$set(bindings.longitude, longitude, false)
            this.$wire.$set(bindings.source, data.source, false)
            this.$wire.$set(bindings.precision, data.precision, true)
            this.dispatchLocationEvent(root, 'filament-location:pin-moved', data)
        },

        dispatchLocationEvent(root, name, detail) {
            if (!root?.isConnected) {
                return
            }

            root.dispatchEvent(new CustomEvent(name, {
                bubbles: true,
                detail,
            }))
        },
    }
}
