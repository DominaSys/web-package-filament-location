<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use RuntimeException;

final readonly class AddressDataWriter
{
    public function __construct(
        private AddressDataPathResolver $pathResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $dataset
     */
    public function write(string $countryCode, array $dataset): string
    {
        $path = $this->pathResolver->fileForCountry($countryCode);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create data directory: %s', $directory));
        }

        $json = json_encode(
            $dataset,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException('Unable to encode address dataset as JSON.');
        }

        $tempPath = tempnam($directory, 'address-data-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for address dataset.');
        }

        if (file_put_contents($tempPath, $json) === false) {
            @unlink($tempPath);

            throw new RuntimeException('Unable to write temporary address dataset file.');
        }

        if (file_exists($path) && ! @unlink($path)) {
            @unlink($tempPath);

            throw new RuntimeException(sprintf('Unable to replace existing address dataset file %s.', $path));
        }

        if (! @rename($tempPath, $path)) {
            @unlink($tempPath);

            throw new RuntimeException(sprintf('Unable to move temporary address dataset file to %s.', $path));
        }

        return $path;
    }
}
