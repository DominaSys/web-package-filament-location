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
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('google-place-picker', package: 'dominasys/filament-location'))]"
        x-data="googlePlacePicker({
            apiKey: @js(config('location.google.browser_key')),
            language: @js(config('location.google.language', 'pt-BR')),
            region: @js(config('location.google.region', 'BR')),
            mapId: @js(config('location.google.map_id')),
            statePath: @js($shouldStoreSelectionState() ? $statePath : null),
            bindings: @js($getBoundStatePaths()),
            initialLatitude: @js($get($getBinding('latitude'))),
            initialLongitude: @js($get($getBinding('longitude'))),
            placeholder: @js($getSearchPlaceholder()),
            zoom: @js($getZoom()),
            metadata: @js($getLocationMetadata()),
        })"
        class="fi-fo-google-place-picker space-y-3"
    >
        <x-filament::input.wrapper
            class="fi-fo-google-place-picker-search"
            x-on:focus-input.stop="$el.querySelector('input')?.focus()"
        >
            <input
                x-model="query"
                x-on:input.debounce.350ms="searchPlaces()"
                x-on:keydown.escape="clearSuggestions()"
                x-bind:placeholder="placeholder"
                x-bind:aria-expanded="suggestions.length > 0"
                x-bind:aria-controls="$id('place-suggestions')"
                type="search"
                autocomplete="off"
                aria-autocomplete="list"
                role="combobox"
                class="fi-fo-google-place-picker-input"
            />
        </x-filament::input.wrapper>

        <div
            x-show="isSearching || (hasSearched && suggestions.length === 0) || suggestions.length > 0"
            x-bind:id="$id('place-suggestions')"
            class="fi-fo-google-place-picker-results"
            role="listbox"
            aria-label="Sugestões de locais"
        >
            <p x-show="isSearching" class="fi-fo-google-place-picker-status" role="status">
                Buscando locais…
            </p>

            <p x-show="! isSearching && hasSearched && suggestions.length === 0" class="fi-fo-google-place-picker-status" role="status">
                Nenhum local encontrado. Tente informar mais detalhes.
            </p>

            <template x-for="suggestion in suggestions" x-bind:key="suggestion.placeId">
                <button
                    x-on:click="selectSuggestion(suggestion)"
                    x-bind:disabled="isSelecting"
                    type="button"
                    class="fi-fo-google-place-picker-result"
                    role="option"
                >
                    <span x-text="suggestion.mainText"></span>
                    <span
                        class="fi-fo-google-place-picker-result-detail"
                        x-show="suggestion.secondaryText"
                        x-text="suggestion.secondaryText"
                    ></span>
                </button>
            </template>
        </div>

        <div class="fi-fo-google-place-picker-map-container" x-bind:aria-busy="isSelecting">
            <div
                x-ref="map"
                class="w-full overflow-hidden rounded-lg border border-gray-300 bg-gray-100 dark:border-white/10 dark:bg-gray-900"
                style="height: {{ $getMapHeight() }}px"
            ></div>

            <div
                x-cloak
                x-show="isSelecting"
                class="fi-fo-google-place-picker-map-loading"
                role="status"
                aria-live="polite"
                aria-label="Carregando local no mapa"
            >
                <x-filament::loading-indicator class="size-8" />
                <span>Carregando local no mapa…</span>
            </div>
        </div>

        <p x-show="error" x-text="error" class="text-sm text-danger-600 dark:text-danger-400"></p>
    </div>
</x-dynamic-component>
