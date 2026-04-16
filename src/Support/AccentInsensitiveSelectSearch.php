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
        .replace(/[^a-z0-9]+/gi, ' ')
        .toLowerCase()
        .trim()

    const tokenize = (value) => normalize(value).split(/\s+/).filter(Boolean)

    const levenshtein = (left, right) => {
        if (left === right) {
            return 0
        }

        if (left.length === 0) {
            return right.length
        }

        if (right.length === 0) {
            return left.length
        }

        let previousRow = Array.from({ length: right.length + 1 }, (_, index) => index)

        for (let rowIndex = 0; rowIndex < left.length; rowIndex++) {
            const currentRow = [rowIndex + 1]
            const leftChar = left.charAt(rowIndex)

            for (let columnIndex = 0; columnIndex < right.length; columnIndex++) {
                const insertionCost = currentRow[columnIndex] + 1
                const deletionCost = previousRow[columnIndex + 1] + 1
                const substitutionCost = previousRow[columnIndex] + (leftChar === right.charAt(columnIndex) ? 0 : 1)

                currentRow.push(Math.min(insertionCost, deletionCost, substitutionCost))
            }

            previousRow = currentRow
        }

        return previousRow[right.length]
    }

    const maxDistance = (query) => {
        if (query.length <= 4) {
            return 1
        }

        if (query.length <= 7) {
            return 2
        }

        return 3
    }

    const matchScore = (candidate, query) => {
        const normalizedCandidate = normalize(candidate)
        const normalizedQuery = normalize(query)

        if (normalizedQuery === '') {
            return 0
        }

        if (normalizedCandidate.includes(normalizedQuery) || normalizedQuery.includes(normalizedCandidate)) {
            return 0
        }

        const candidateTokens = tokenize(candidate)
        const queryTokens = tokenize(query)
        const threshold = maxDistance(normalizedQuery)
        let bestScore = Number.POSITIVE_INFINITY

        for (const queryToken of queryTokens.length > 0 ? queryTokens : [normalizedQuery]) {
            for (const candidateToken of candidateTokens.length > 0 ? candidateTokens : [normalizedCandidate]) {
                if (
                    candidateToken.includes(queryToken)
                    || queryToken.includes(candidateToken)
                ) {
                    return 0
                }

                const distance = levenshtein(queryToken, candidateToken)
                if (distance <= threshold) {
                    bestScore = Math.min(bestScore, distance)
                }
            }
        }

        if (bestScore !== Number.POSITIVE_INFINITY) {
            return bestScore
        }

        const distanceToFullLabel = levenshtein(normalizedQuery, normalizedCandidate)
        return distanceToFullLabel <= threshold ? distanceToFullLabel : Number.POSITIVE_INFINITY
    }

    const normalizedQuery = normalize(query)
    const searchableOptionFields = this.searchableOptionFields
    const searchByLabel = searchableOptionFields.includes('label')
    const searchByValue = searchableOptionFields.includes('value')
    const options = []

    for (const option of this.originalOptions) {
        if (option.options && Array.isArray(option.options)) {
            const filteredOptions = option.options
                .map((groupedOption) => {
                    const labelScore = searchByLabel ? matchScore(groupedOption.label, query) : Number.POSITIVE_INFINITY
                    const valueScore = searchByValue ? matchScore(groupedOption.value, query) : Number.POSITIVE_INFINITY
                    const score = Math.min(labelScore, valueScore)

                    return score === Number.POSITIVE_INFINITY
                        ? null
                        : { ...groupedOption, __score: score }
                })
                .filter((groupedOption) => groupedOption !== null)
                .sort((left, right) => left.__score - right.__score || normalize(left.label).localeCompare(normalize(right.label)))

            if (filteredOptions.length > 0) {
                const bestScore = filteredOptions[0].__score ?? 0

                options.push({
                    label: option.label,
                    options: filteredOptions,
                    __score: bestScore,
                })
            }

            continue
        }

        const labelScore = searchByLabel ? matchScore(option.label, query) : Number.POSITIVE_INFINITY
        const valueScore = searchByValue ? matchScore(option.value, query) : Number.POSITIVE_INFINITY
        const score = Math.min(labelScore, valueScore)

        if (score !== Number.POSITIVE_INFINITY) {
            options.push({
                ...option,
                __score: score,
            })
        }
    }

    options.sort((left, right) => left.__score - right.__score || normalize(left.label).localeCompare(normalize(right.label)))

    for (const option of options) {
        if (Object.prototype.hasOwnProperty.call(option, '__score')) {
            delete option.__score
        }
        if (option.options) {
            for (const groupedOption of option.options) {
                if (Object.prototype.hasOwnProperty.call(groupedOption, '__score')) {
                    delete groupedOption.__score
                }
            }
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
