<?php

namespace Tests\Feature;

use App\Storage\CatboxStorage;
use App\Storage\CatboxUploadFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the Catbox media host.
 *
 * The central design rule is that a stored media value is the absolute URL the
 * host assigned, never the path the caller passed in — because the host names
 * every file itself. These tests pin that rule down, along with the behaviours
 * that would otherwise fail silently in production.
 */
class CatboxStorageTest extends TestCase
{
    use RefreshDatabase;

    private CatboxStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = app(CatboxStorage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Uploading
    |--------------------------------------------------------------------------
    */

    public function test_an_upload_returns_an_absolute_hosted_url(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg', 40, 40), 'students/profiles');

        $this->assertStringStartsWith('https://files.catbox.moe/', $url);
    }

    public function test_the_returned_url_is_what_gets_recorded(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');

        $this->assertContains($url, $this->catbox->uploadedUrls());
    }

    public function test_the_hosted_url_does_not_contain_the_caller_supplied_path(): void
    {
        // The directory argument is provenance only. If it leaked into the stored
        // value, the application's URLs would not resolve.
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');

        $this->assertStringNotContainsString('students/profiles', $url);
    }

    public function test_the_extension_is_preserved_for_content_type(): void
    {
        // The host discards the filename, so the extension is the only thing that
        // decides how the object is later served.
        $png = $this->storage->store(UploadedFile::fake()->image('photo.png'), 'students/profiles');

        $this->assertStringEndsWith('.png', $png);
    }

    public function test_storing_an_existing_url_is_idempotent(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');

        $this->assertSame($url, $this->storage->store($url, 'students/profiles'));
    }

    /*
    |--------------------------------------------------------------------------
    | Resolving stored values back to URLs
    |--------------------------------------------------------------------------
    */

    public function test_a_hosted_url_resolves_to_itself(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');

        $this->assertSame($url, $this->storage->url($url));
    }

    public function test_a_legacy_relative_path_still_resolves(): void
    {
        // Values written before the move to hosted storage must keep rendering.
        $this->assertSame(
            'https://files.catbox.moe/courses/legacy.png',
            $this->storage->url('courses/legacy.png')
        );
    }

    public function test_blank_values_resolve_to_null(): void
    {
        $this->assertNull($this->storage->url(null));
        $this->assertNull($this->storage->url(''));
    }

    public function test_is_remote_distinguishes_hosted_values_from_paths(): void
    {
        $this->assertTrue($this->storage->isRemote('https://files.catbox.moe/abc.png'));
        $this->assertFalse($this->storage->isRemote('students/profiles/abc.png'));
        $this->assertFalse($this->storage->isRemote(null));
    }

    /*
    |--------------------------------------------------------------------------
    | Reading back
    |--------------------------------------------------------------------------
    */

    public function test_a_hosted_file_can_be_read_back(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.png'), 'students/profiles');

        $this->assertTrue(Storage::disk('catbox')->exists($url));
        $this->assertNotEmpty(Storage::disk('catbox')->get($url));
    }

    public function test_a_hosted_url_is_never_normalised_into_a_broken_path(): void
    {
        // Flysystem's path normaliser collapses "http://" to "http:/", which
        // silently breaks every read. This is the regression guard for that.
        $url = $this->storage->store(UploadedFile::fake()->image('photo.png'), 'students/profiles');

        $this->assertStringNotContainsString('http:/', Storage::disk('catbox')->getAdapter()->resolve($url));
        $this->assertNotEmpty(Storage::disk('catbox')->get($url));
    }

    public function test_metadata_is_reported_for_a_hosted_file(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.png'), 'students/profiles');
        $disk = Storage::disk('catbox');

        $this->assertGreaterThan(0, $disk->size($url));
        $this->assertSame('public', $disk->visibility($url));
    }

    /*
    |--------------------------------------------------------------------------
    | Deleting
    |--------------------------------------------------------------------------
    */

    public function test_unlinking_never_throws(): void
    {
        $url = $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');

        // The host cannot delete without an account key, so forgetting a
        // reference must not fail the operation that triggered it.
        $this->storage->forget($url);

        $this->assertTrue(true, 'forget() completed without throwing.');
    }

    public function test_unlinking_an_external_url_is_a_no_op(): void
    {
        // An administrator's pasted URL belongs to someone else.
        $this->storage->forget('https://example.com/someone-elses-image.png');

        $this->assertTrue(true);
    }

    public function test_unlinking_blank_values_is_a_no_op(): void
    {
        $this->storage->forget(null);
        $this->storage->forget('');

        $this->assertTrue(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Failure handling
    |--------------------------------------------------------------------------
    */

    public function test_an_unreachable_host_raises_a_clear_error(): void
    {
        $this->catbox->unreachable();

        $this->expectException(CatboxUploadFailed::class);
        $this->expectExceptionMessageMatches('/Could not reach the media host/');

        $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');
    }

    public function test_a_rejected_upload_surfaces_the_host_s_reason(): void
    {
        $this->catbox->rejectWith('File is too large.');

        $this->expectException(CatboxUploadFailed::class);
        $this->expectExceptionMessageMatches('/refused this file.*too large/i');

        $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');
    }

    public function test_a_failed_upload_does_not_leave_a_path_to_store(): void
    {
        // Callers persist whatever store() returns, so it must never hand back a
        // path that was never actually uploaded.
        $this->catbox->rejectWith('No files given.');

        try {
            $this->storage->store(UploadedFile::fake()->image('photo.jpg'), 'students/profiles');
            $this->fail('Expected the upload to be rejected.');
        } catch (CatboxUploadFailed) {
            $this->assertCount(0, $this->catbox->uploadedUrls());
        }
    }
}
