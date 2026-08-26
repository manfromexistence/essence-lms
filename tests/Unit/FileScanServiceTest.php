<?php

namespace Tests\Unit;

use App\Services\FileScanService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileScanServiceTest extends TestCase
{
    private FileScanService $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new FileScanService();
    }

    public function test_valid_png_is_accepted(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'proof.png',
            "\x89PNG\r\n\x1A\n" . str_repeat("\x00", 128)
        );

        $this->assertNull($this->scanner->inspect($file, ['png']));
    }

    public function test_clean_pdf_is_accepted(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'receipt.pdf',
            "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF"
        );

        $this->assertNull($this->scanner->inspect($file, ['pdf']));
    }

    public function test_png_with_embedded_php_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'evil.png',
            "\x89PNG\r\n\x1A\n<?php system(\$_GET['cmd']); ?>"
        );

        $this->assertNotNull($this->scanner->inspect($file, ['png']));
    }

    public function test_content_not_matching_declared_type_is_rejected(): void
    {
        // A PHP script renamed to .png must fail signature validation.
        $file = UploadedFile::fake()->createWithContent(
            'shell.png',
            "<?php echo 'pwned';"
        );

        $this->assertNotNull($this->scanner->inspect($file, ['png']));
    }

    public function test_php_webshell_upload_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'shell.php',
            "<?php eval(\$_POST['x']);"
        );

        $this->assertNotNull($this->scanner->inspect($file));
    }

    public function test_pdf_with_javascript_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'malicious.pdf',
            "%PDF-1.4\n1 0 obj << /JavaScript (app.alert(1)) >> endobj\n%%EOF"
        );

        $this->assertNotNull($this->scanner->inspect($file, ['pdf']));
    }

    public function test_extension_outside_whitelist_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('archive.zip', "PK\x03\x04" . str_repeat("\x00", 32));

        $this->assertNotNull($this->scanner->inspect($file, ['jpg', 'png', 'pdf']));
    }

    public function test_scanning_can_be_disabled_by_config(): void
    {
        config(['uploads.scan.enabled' => false]);

        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php evil();');

        $this->assertNull($this->scanner->inspect($file));
    }
}
