<?php

namespace App\Services;

/**
 * Normalises the certificate designer's element list.
 *
 * The designer stores its layout as free-form JSON in
 * `certificate_templates.layout_config`, and the renderer emits element
 * properties straight into an unescaped `style="{!! ... !!}"` attribute. Any
 * element property that reaches that string unvalidated is an injection point:
 * a `color` of `red" onmouseover="fetch(...)` closes the attribute and installs
 * an event handler.
 *
 * Because templates are writable by `admin` and rendered in `super-admin` and
 * student browsers, that is a vertical privilege escalation, so the properties
 * are constrained here rather than trusted at the point of use.
 *
 * The rule throughout is: numbers become numbers inside a known range, and
 * anything that is a keyword or a URL must appear in a whitelist. Unknown keys
 * are dropped rather than passed through, so adding a property to the designer
 * later cannot accidentally widen this.
 */
class CertificateLayoutSanitiser
{
    /**
     * Font stacks the renderer knows how to map.
     *
     * @var array<int, string>
     */
    private const FONTS = [
        'Georgia, serif',
        'Arial, sans-serif',
        'Courier New, monospace',
        '"Brush Script MT", cursive',
        'serif',
        'sans',
        'mono',
        'cursive',
    ];

    /** @var array<int, string> */
    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    /** @var array<int, string> */
    private const IMAGE_FIELDS = ['logo', 'signature', 'background', 'qr', 'custom'];

    /** @var array<string, array{0: int, 1: int}> Property => [min, max]. */
    private const NUMERIC_RANGES = [
        'x' => [-500, 5000],
        'y' => [-500, 5000],
        'width' => [1, 5000],
        'height' => [1, 5000],
        'fontSize' => [1, 400],
        'letterSpacing' => [-50, 200],
        'rotation' => [-180, 180],
        'opacity' => [0, 1],
    ];

    /** Only simple colour notations; gradients and expressions are not accepted. */
    private const COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';

    private const MAX_ELEMENTS = 300;

    private const MAX_TEXT_LENGTH = 500;

    /**
     * @return array{elements: array<int, array<string, mixed>>, background_opacity: float|int}
     */
    public function sanitise(mixed $layout): array
    {
        $layout = is_array($layout) ? $layout : [];

        $elements = $layout['elements'] ?? [];

        // A template saved before the designer gained an `elements` key stores
        // the list at the top level.
        if (! is_array($elements) || $elements === []) {
            $elements = array_is_list($layout) ? $layout : [];
        }

        $elements = is_array($elements) ? $elements : [];

        if (count($elements) > self::MAX_ELEMENTS) {
            $elements = array_slice($elements, 0, self::MAX_ELEMENTS);
        }

        $sanitised = [];

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $clean = $this->sanitiseElement($element);

            if ($clean !== null) {
                $sanitised[] = $clean;
            }
        }

        return [
            'elements' => $sanitised,
            'background_opacity' => $this->clamp(
                $layout['background_opacity'] ?? 0.6,
                0.0,
                1.0,
                0.6
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array<string, mixed>|null
     */
    private function sanitiseElement(array $element): ?array
    {
        $type = $element['type'] ?? 'text';

        if (! in_array($type, ['text', 'image'], true)) {
            return null;
        }

        $clean = ['type' => $type];

        foreach (self::NUMERIC_RANGES as $property => [$min, $max]) {
            if (array_key_exists($property, $element)) {
                $clean[$property] = $this->clamp($element[$property], $min, $max, 0);
            }
        }

        if ($type === 'text') {
            $clean['content'] = $this->text($element['content'] ?? '');
            $clean['color'] = $this->colour($element['color'] ?? null);
            $clean['fontFamily'] = $this->choice($element['fontFamily'] ?? null, self::FONTS, 'Georgia, serif');
            $clean['align'] = $this->choice($element['align'] ?? null, self::ALIGNMENTS, 'center');

            foreach (['bold', 'italic', 'underline'] as $flag) {
                $clean[$flag] = (bool) ($element[$flag] ?? false);
            }
        } else {
            $clean['imageField'] = $this->choice($element['imageField'] ?? null, self::IMAGE_FIELDS, 'logo');

            // Only an absolute http(s) URL is acceptable, so the renderer cannot
            // be pointed at a local file or a javascript: URI.
            $url = $element['imageUrl'] ?? null;
            $clean['imageUrl'] = is_string($url) && preg_match('#^https?://\S+$#i', trim($url)) === 1
                ? trim($url)
                : null;
        }

        return $clean;
    }

    private function text(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = (string) $value;

        return mb_substr($value, 0, self::MAX_TEXT_LENGTH);
    }

    private function colour(mixed $value): string
    {
        if (! is_string($value) || preg_match(self::COLOR_PATTERN, trim($value)) !== 1) {
            return '#111827';
        }

        return trim($value);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function choice(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Constrain a numeric property to a range.
     *
     * Integer bounds yield an integer so the stored layout stays readable in
     * the designer, which round-trips these values through the DOM.
     */
    private function clamp(mixed $value, int|float $min, int|float $max, int|float $default): int|float
    {
        if (! is_numeric($value)) {
            return $default;
        }

        $clamped = max($min, min($max, (float) $value));

        return (is_int($min) && is_int($max)) ? (int) $clamped : $clamped;
    }
}
