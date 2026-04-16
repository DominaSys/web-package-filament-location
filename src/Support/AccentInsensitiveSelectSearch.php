<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Support;

final class AccentInsensitiveSelectSearch
{
    public static function xInit(): string
    {
        return <<<'JS'
select.filterOptions = function (query) {
    const normalize = (value) => String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()

    const normalizedQuery = normalize(query)
    const searchableOptionFields = this.searchableOptionFields
    const searchByLabel = searchableOptionFields.includes('label')
    const searchByValue = searchableOptionFields.includes('value')
    const options = []

    for (const option of this.originalOptions) {
        if (option.options && Array.isArray(option.options)) {
            const filteredOptions = option.options.filter((groupedOption) => {
                return (searchByLabel && normalize(groupedOption.label).includes(normalizedQuery))
                    || (searchByValue && normalize(groupedOption.value).includes(normalizedQuery))
            })

            if (filteredOptions.length > 0) {
                options.push({
                    label: option.label,
                    options: filteredOptions,
                })
            }

            continue
        }

        if (
            (searchByLabel && normalize(option.label).includes(normalizedQuery))
            || (searchByValue && normalize(option.value).includes(normalizedQuery))
        ) {
            options.push(option)
        }
    }

    this.options = options
    this.renderOptions()

    if (this.options.length === 0) {
        this.showNoResultsMessage()
    }

    if (this.isOpen) {
        this.positionDropdown()
    }
}
JS;
    }
}
