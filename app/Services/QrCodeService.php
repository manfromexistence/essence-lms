<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use RuntimeException;

/**
 * Renders the QR code printed on a course certificate.
 *
 * Scanning the code lands on the student's public verification profile, which is
 * what makes a printed certificate checkable without a phone call to the
 * institute. Codes are generated locally rather than fetched from a QR API, so
 * printing never depends on a third party being reachable.
 *
 * Two output formats are needed and they are not interchangeable:
 *
 *  - {@see pngDataUri()} returns a `data:image/png;base64,...` string. This is
 *    what certificates need: dompdf runs with `enable_remote => false`, so a QR
 *    referencing an http URL would render as an empty box in a generated PDF.
 *    A data URI is the one form dompdf reliably accepts.
 *  - {@see svg()} returns markup for the on-screen designer preview, which is
 *    scaled with a CSS transform and would blur if it were a raster image.
 */
class QrCodeService
{
    /**
     * Quiet zone, in modules.
     *
     * Not optional: a QR printed flush against surrounding artwork fails to
     * scan on a phone, which is the entire point of printing one.
     */
    private const MARGIN = 2;

    /** Enough error correction to survive ink spread on a printed certificate. */
    private const ERROR_CORRECTION_BITS = 1;

    /**
     * Payload encoding.
     *
     * The library defaults to ISO-8859-1, which would mangle any non-ASCII
     * character in a URL query string, so UTF-8 is requested explicitly.
     */
    private const ENCODING = 'UTF-8';

    /**
     * The code as a base64 PNG data URI, ready for an <img src> or a PDF.
     *
     * @param  int  $size  Width and height of the square image, in pixels.
     */
    public function pngDataUri(string $payload, int $size = 300): string
    {
        $png = $this->png($payload, $size);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * The code as raw PNG bytes.
     */
    public function png(string $payload, int $size = 300): string
    {
        $this->assertRendererCanDraw();

        $renderer = new GDLibRenderer(
            $size,
            self::MARGIN,
            'png',
            9,
            $this->fill(),
        );

        return (new Writer($renderer))->writeString($payload, self::ENCODING, ErrorCorrectionLevel::forBits(self::ERROR_CORRECTION_BITS));
    }

    /**
     * The code as inline SVG markup.
     */
    public function svg(string $payload, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, self::MARGIN, null, null, $this->fill()),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($payload, self::ENCODING, ErrorCorrectionLevel::forBits(self::ERROR_CORRECTION_BITS));
    }

    /**
     * Black on white.
     *
     * A QR relies on a fixed luminance contrast ratio; the branded or inverted
     * variants some generators emit are markedly less reliable in print.
     */
    private function fill(): Fill
    {
        return Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0));
    }

    private function assertRendererCanDraw(): void
    {
        if (! extension_loaded('gd') || ! function_exists('gd_info')) {
            throw new RuntimeException(
                'The GD extension is required to render certificate QR codes. Install php-gd or switch the renderer.'
            );
        }
    }
}
