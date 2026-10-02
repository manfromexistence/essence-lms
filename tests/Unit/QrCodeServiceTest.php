<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

/**
 * The QR printed on a certificate is the whole basis of remote verification, so
 * these assert the things that would silently produce an unscannable code: a
 * blank image, a wrong pixel size, or markup that will not parse.
 */
class QrCodeServiceTest extends TestCase
{
    private QrCodeService $qr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qr = app(QrCodeService::class);
    }

    public function test_png_data_uri_is_a_real_png(): void
    {
        $uri = $this->qr->pngDataUri('https://example.test/verify/student/abc', 300);

        $this->assertStringStartsWith('data:image/png;base64,', $uri);

        $bytes = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);

        $this->assertNotFalse($bytes, 'The data URI must contain valid base64.');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($bytes, 0, 8), 'Must be a PNG signature.');
    }

    public function test_png_is_the_requested_pixel_size(): void
    {
        foreach ([150, 300, 600] as $size) {
            $bytes = $this->qr->png('https://example.test/verify/student/abc', $size);

            $width = unpack('N', substr($bytes, 16, 4))[1];
            $height = unpack('N', substr($bytes, 20, 4))[1];

            $this->assertSame($size, $width, "Width should be {$size}px.");
            $this->assertSame($size, $height, "Height should be {$size}px.");
        }
    }

    public function test_qr_actually_contains_modules(): void
    {
        // Guards against a blank square, which would look fine and never scan.
        $image = imagecreatefromstring($this->qr->png('https://example.test/verify/student/abc', 300));

        $dark = 0;
        $light = 0;

        for ($y = 0; $y < 300; $y += 3) {
            for ($x = 0; $x < 300; $x += 3) {
                $rgb = imagecolorat($image, $x, $y);
                $luminance = (($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF);

                $luminance < 200 ? $dark++ : $light++;
            }
        }

        imagedestroy($image);

        $this->assertGreaterThan(100, $dark, 'The code must contain dark modules.');
        $this->assertGreaterThan(100, $light, 'The code must keep a light quiet zone around it.');
    }

    public function test_the_three_finder_patterns_sit_in_the_right_corners(): void
    {
        // Every QR needs a finder in top-left, top-right and bottom-left. If these
        // are missing the image is not a QR code at all.
        $image = imagecreatefromstring($this->qr->png('https://example.test/verify/student/abc', 300));

        $corners = [
            'top-left' => [20, 20],
            'top-right' => [280, 20],
            'bottom-left' => [20, 280],
        ];

        foreach ($corners as $label => [$x, $y]) {
            $rgb = imagecolorat($image, $x, $y);
            $luminance = (($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF);

            $this->assertLessThan(200, $luminance, "The {$label} finder pattern is missing.");
        }

        imagedestroy($image);
    }

    public function test_svg_is_well_formed_and_sized(): void
    {
        $svg = $this->qr->svg('https://example.test/verify/student/abc', 300);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('width="300"', $svg);
        $this->assertNotFalse(
            @simplexml_load_string($svg),
            'The SVG must be parseable, or the designer preview breaks.'
        );
    }

    public function test_different_payloads_produce_different_codes(): void
    {
        $base = $this->qr->png('https://example.test/verify/student/aaa', 300);
        $other = $this->qr->png('https://example.test/verify/student/bbb', 300);

        $this->assertNotSame($base, $other, 'Each student must get a distinct code.');
    }

    public function test_data_uri_is_safe_inside_an_html_attribute(): void
    {
        $uri = $this->qr->pngDataUri('https://example.test/verify/student/abc', 300);

        $this->assertStringNotContainsString('"', $uri);
        $this->assertStringNotContainsString('<', $uri);
        $this->assertStringNotContainsString('>', $uri);
    }

    public function test_long_verification_urls_are_accepted(): void
    {
        // The token is 40 characters; a full URL plus host must still encode.
        $url = 'https://portal.dhakaitinstitute.com/verify/student/'.str_repeat('a1b2c3', 7);

        $this->assertStringStartsWith('data:image/png;base64,', $this->qr->pngDataUri($url, 600));
    }
}
