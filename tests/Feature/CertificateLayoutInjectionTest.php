<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the stored XSS in the certificate designer.
 *
 * `layout_config` is free-form JSON written by `admin` and rendered into an
 * unescaped `style="{!! ... !!}"` attribute. Element properties that reached
 * that string verbatim could close the attribute and install an event handler,
 * which executed in the `super-admin` browser and in every student's browser
 * that opened the certificate.
 */
class CertificateLayoutInjectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * The payload that broke out of the style attribute before the fix.
     */
    private function maliciousLayout(): array
    {
        return [
            'elements' => [
                [
                    'type' => 'text',
                    'content' => 'Certificate',
                    'x' => '50" onmouseover="fetch(\'/steal\')',
                    'y' => 50,
                    'width' => 700,
                    'color' => 'red" onmouseover="fetch(\'/steal\')',
                    'fontFamily' => 'Georgia; background:url(javascript:alert(1))',
                    'align' => 'center"><script>alert(1)</script>',
                    'opacity' => '1; position:fixed; inset:0',
                ],
            ],
            'background_opacity' => '0.6; background-image:url(javascript:alert(1))',
        ];
    }

    public function test_a_malicious_layout_is_neutralised_on_save(): void
    {
        $template = CertificateTemplate::create([
            'name' => 'Injection Test',
            'type' => 'course_completion',
            'is_active' => true,
            'width' => 1200,
            'height' => 900,
        ]);

        $this->actingAs($this->admin())
            ->put(route('dashboard.certificates.templates.update', $template), [
                'name' => 'Injection Test',
                'type' => 'course_completion',
                'layout_config' => json_encode($this->maliciousLayout()),
            ])
            ->assertRedirect();

        $stored = json_encode($template->fresh()->layout_config);

        foreach (['onmouseover', 'javascript:', '<script', '/steal'] as $needle) {
            $this->assertStringNotContainsString($needle, $stored, "The stored layout still contains {$needle}.");
        }
    }

    public function test_rendered_certificate_markup_carries_no_injected_handler(): void
    {
        $template = CertificateTemplate::create([
            'name' => 'Injection Test',
            'type' => 'course_completion',
            'is_active' => true,
            'width' => 1200,
            'height' => 900,
            // Bypasses the controller to prove the renderer is safe on its own,
            // which is what protects templates stored before the fix.
            'layout_config' => $this->maliciousLayout(),
        ]);

        $html = view('dashboard.certificates.partials.render-elements', [
            'template' => $template,
            'values' => [],
        ])->render();

        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<script>alert', $html);

        // The element must still render, just safely.
        $this->assertStringContainsString('cert-elem', $html);
    }

    public function test_numeric_properties_are_clamped(): void
    {
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $result = $sanitiser->sanitise([
            'elements' => [[
                'type' => 'text',
                'content' => 'x',
                'x' => 999999,
                'y' => -999999,
                'width' => 0,
                'fontSize' => 'abc',
                'rotation' => 10000,
                'opacity' => 50,
            ]],
        ]);

        $element = $result['elements'][0];

        $this->assertSame(5000, $element['x']);
        $this->assertSame(-500, $element['y']);
        $this->assertGreaterThanOrEqual(1, $element['width']);
        $this->assertLessThanOrEqual(400, $element['fontSize']);
        $this->assertLessThanOrEqual(180, $element['rotation']);
        $this->assertLessThanOrEqual(1, $element['opacity']);
    }

    public function test_colours_must_be_hex(): void
    {
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $result = $sanitiser->sanitise([
            'elements' => [
                ['type' => 'text', 'content' => 'a', 'color' => '#fff'],
                ['type' => 'text', 'content' => 'b', 'color' => 'rgb(1,2,3)'],
                ['type' => 'text', 'content' => 'c', 'color' => '#111827'],
            ],
        ]);

        $this->assertSame('#fff', $result['elements'][0]['color'], 'Valid 3-digit hex is preserved.');
        $this->assertSame('#111827', $result['elements'][1]['color'], 'A non-hex colour must fall back.');
        $this->assertSame('#111827', $result['elements'][2]['color']);
    }

    public function test_unknown_element_types_and_keys_are_dropped(): void
    {
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $result = $sanitiser->sanitise([
            'elements' => [
                ['type' => 'iframe', 'src' => 'https://evil.test'],
                ['type' => 'text', 'content' => 'ok', 'onclick' => 'alert(1)', 'style' => 'x'],
            ],
        ]);

        $this->assertCount(1, $result['elements'], 'The iframe element must be dropped entirely.');
        $this->assertArrayNotHasKey('onclick', $result['elements'][0]);
        $this->assertArrayNotHasKey('style', $result['elements'][0]);
    }

    public function test_image_elements_reject_non_http_urls(): void
    {
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $result = $sanitiser->sanitise([
            'elements' => [
                ['type' => 'image', 'imageField' => 'custom', 'imageUrl' => 'file:///etc/passwd'],
                ['type' => 'image', 'imageField' => 'custom', 'imageUrl' => 'javascript:alert(1)'],
                ['type' => 'image', 'imageField' => 'custom', 'imageUrl' => 'https://cdn.test/a.png'],
            ],
        ]);

        $this->assertNull($result['elements'][0]['imageUrl']);
        $this->assertNull($result['elements'][1]['imageUrl']);
        $this->assertSame('https://cdn.test/a.png', $result['elements'][2]['imageUrl']);
    }

    public function test_the_qr_element_survives_sanitisation(): void
    {
        // The verification QR depends on this element type reaching the renderer.
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $result = $sanitiser->sanitise([
            'elements' => [
                ['type' => 'image', 'imageField' => 'qr', 'x' => 1000, 'y' => 600, 'width' => 150, 'height' => 150],
            ],
        ]);

        $this->assertSame('qr', $result['elements'][0]['imageField']);
        $this->assertSame(150, $result['elements'][0]['width']);
    }

    public function test_element_count_is_bounded(): void
    {
        $sanitiser = app(\App\Services\CertificateLayoutSanitiser::class);

        $elements = array_map(
            fn ($i) => ['type' => 'text', 'content' => 'x' . $i],
            range(1, 500)
        );

        $this->assertLessThanOrEqual(300, count($sanitiser->sanitise(['elements' => $elements])['elements']));
    }
}