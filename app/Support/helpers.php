<?php

if (! function_exists('media_url')) {
    /**
     * Resolve a stored image/media value to a working URL.
     *
     * Media lives on Catbox, so a stored value is normally an absolute
     * `https://files.catbox.moe/...` URL. Values written before that migration
     * are still storage-relative paths (`courses/x.png`), and brand assets are
     * webroot paths (`images/brand/logo.png`), so all three shapes are accepted.
     *
     * Keeping every shape working in one place means a column can be migrated
     * row by row without breaking the views that read it.
     *
     * @param  string|null  $value
     * @param  mixed  $fallback
     * @return string|null
     */
    function media_url(?string $value, $fallback = null): ?string
    {
        if (blank($value)) {
            return $fallback;
        }

        $value = trim($value);

        // Already hosted, or any other absolute URL.
        if (preg_match('#^https?://\S+$#i', $value) === 1) {
            return $value;
        }

        // A webroot path such as /images/brand/logo.png.
        if (str_starts_with($value, '/')) {
            return asset(ltrim($value, '/'));
        }

        // Anything left is a path on the local public disk.
        return asset('storage/' . ltrim($value, '/'));
    }
}