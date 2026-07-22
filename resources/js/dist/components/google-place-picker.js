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

function normalizePlace(place, predictionText, metadata) {
    const components = place.addressComponents ?? []

    return {
        place_id: place.id ?? null,
        name: predictionText || null,
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
    return {
        error: null,
        map: null,
        marker: null,
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
                const [{ Map }, { AdvancedMarkerElement, CollisionBehavior, PinElement }, { PlaceAutocompleteElement }] = await Promise.all([
                    googleMaps.importLibrary('maps'),
                    googleMaps.importLibrary('marker'),
                    googleMaps.importLibrary('places'),
                ])

                const hasCoordinates = Number.isFinite(Number(initialLatitude)) && Number.isFinite(Number(initialLongitude))
                const center = hasCoordinates
                    ? { lat: Number(initialLatitude), lng: Number(initialLongitude) }
                    : { lat: -14.235, lng: -51.9253 }

                this.map = new Map(this.$refs.map, {
                    center,
                    zoom: hasCoordinates ? zoom : 4,
                    mapId,
                    streetViewControl: false,
                    mapTypeControl: false,
                    clickableIcons: false,
                })

                const pin = new PinElement({
                    scale: 1.1,
                })

                this.marker = new AdvancedMarkerElement({
                    map: this.map,
                    position: center,
                    content: pin.element ?? pin,
                    collisionBehavior: CollisionBehavior.REQUIRED,
                    gmpDraggable: true,
                    zIndex: 1000,
                    title: 'Arraste o pin ou clique no mapa para ajustar o ponto',
                })

                this.marker.addListener('dragend', () => {
                    const position = this.marker.position
                    const latitude = typeof position.lat === 'function' ? position.lat() : position.lat
                    const longitude = typeof position.lng === 'function' ? position.lng() : position.lng

                    this.updateCoordinates(Number(latitude), Number(longitude))
                })

                this.map.addListener('click', (event) => {
                    if (!event.latLng) {
                        return
                    }

                    this.marker.position = event.latLng
                    this.updateCoordinates(event.latLng.lat(), event.latLng.lng())
                })

                const autocomplete = new PlaceAutocompleteElement({
                    includedRegionCodes: region ? [region.toLowerCase()] : undefined,
                })

                autocomplete.placeholder = placeholder
                autocomplete.classList.add('fi-fo-google-place-autocomplete')
                autocomplete.addEventListener('gmp-select', (event) => this.selectPrediction(event.placePrediction))
                autocomplete.addEventListener('gmp-placeselect', (event) => this.selectPrediction(event.placePrediction))
                this.$refs.autocomplete.replaceChildren(autocomplete)
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Não foi possível inicializar o Google Maps.'
            }
        },

        async selectPrediction(prediction) {
            if (!prediction) {
                return
            }

            try {
                const place = prediction.toPlace()

                await place.fetchFields({
                    fields: ['id', 'formattedAddress', 'location', 'addressComponents'],
                })

                const data = normalizePlace(place, prediction.mainText?.text ?? prediction.text?.text ?? null, metadata)

                Object.entries(bindings).forEach(([key, path]) => {
                    this.$wire.set(path, data[key] ?? null, false)
                })

                await this.$wire.set(statePath, data, true)

                const position = { lat: data.latitude, lng: data.longitude }

                this.marker.position = position
                this.map.setCenter(position)
                this.map.setZoom(zoom)
                this.error = null
                this.$root.dispatchEvent(new CustomEvent('filament-location:place-selected', {
                    bubbles: true,
                    detail: data,
                }))
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Não foi possível carregar o endereço selecionado.'
            }
        },

        updateCoordinates(latitude, longitude) {
            const data = {
                latitude,
                longitude,
                source: metadata.pin_source,
                precision: metadata.pin_precision,
            }

            this.$wire.set(bindings.latitude, latitude, false)
            this.$wire.set(bindings.longitude, longitude, false)
            this.$wire.set(bindings.source, data.source, false)
            this.$wire.set(bindings.precision, data.precision, true)
            this.$root.dispatchEvent(new CustomEvent('filament-location:pin-moved', {
                bubbles: true,
                detail: data,
            }))
        },
    }
}
