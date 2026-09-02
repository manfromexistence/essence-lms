<?php

if (!function_exists('media_url')) {
    /**
     * Resolve a stored image/media path to a working URL.
     *
     * Handles:
     *  - External URLs (http/https): returned unchanged.
     *  - Webroot absolute paths (/images/... or /...): returned via asset().
     *  - Storage-relative paths (images/..., uploads/...): returned via asset('storage/...').
     *  - Null/empty: returns null.
     *
     * @param string|null $value
     * @param mixed       $fallback
     * @return string|null
     */
    function media_url(?string $value, $fallback = null): ?string
    {
        if (blank($value)) {
            return $fallback;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $clean = ltrim($value, '/');

        if (str_starts_with($value, '/')) {
            return asset($clean === '' ? '/' : '/' . $clean);
        }

        return asset('storage/' . $clean);
    }
}
