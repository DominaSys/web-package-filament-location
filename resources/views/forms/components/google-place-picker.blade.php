@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('google-place-picker', package: 'dominasys/filament-location') }}"
        x-data="googlePlacePicker({
            apiKey: @js(config('location.google.browser_key')),
            language: @js(config('location.google.language', 'pt-BR')),
            region: @js(config('location.google.region', 'BR')),
            mapId: @js(config('location.google.map_id')),
            statePath: @js($statePath),
            bindings: @js($getBoundStatePaths()),
            initialLatitude: @js($get($getBinding('latitude'))),
            initialLongitude: @js($get($getBinding('longitude'))),
            placeholder: @js($getSearchPlaceholder()),
            zoom: @js($getZoom()),
            metadata: @js($getLocationMetadata()),
        })"
        class="fi-fo-google-place-picker space-y-3"
    >
        <div x-ref="autocomplete" class="min-h-10"></div>

        <div
            x-ref="map"
            class="w-full overflow-hidden rounded-lg border border-gray-300 bg-gray-100 dark:border-white/10 dark:bg-gray-900"
            style="height: {{ $getMapHeight() }}px"
        ></div>

        <p x-show="error" x-text="error" class="text-sm text-danger-600 dark:text-danger-400"></p>
    </div>
</x-dynamic-component>
