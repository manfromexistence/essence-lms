<?php

namespace App\Storage;

use League\Flysystem\CorruptedPathDetected;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;

/**
 * A path normaliser that leaves absolute URLs untouched.
 *
 * Flysystem normalises every path before handing it to the adapter, and its
 * normaliser treats a string purely as a path: "http://host/a.png" collapses to
 * "http:/host/a.png" because the empty segment between the slashes is dropped.
 * For a Catbox-backed disk that is fatal, because the URL *is* the identifier —
 * there is no separate path to resolve it from.
 *
 * Everything that is not a URL still goes through Flysystem's normaliser, so
 * traversal and control-character defences remain in force.
 */
final class UrlAwarePathNormalizer implements PathNormalizer
{
    public function __construct(
        private readonly WhitespacePathNormalizer $paths = new WhitespacePathNormalizer,
    ) {}

    public function normalizePath(string $path): string
    {
        if (preg_match('#^https?://\S+$#i', trim($path)) === 1) {
            return trim($path);
        }

        try {
            return $this->paths->normalizePath($path);
        } catch (CorruptedPathDetected) {
            return ltrim($path, '/');
        }
    }
}
