<?php

namespace App\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The application's front door for remote media storage.
 *
 * Every asset the LMS accepts from a user — images, course video, exam
 * screenshots, payment proofs — is hosted on Catbox rather than on the server's
 * own disk, because course video is far too large for a shared host's upload
 * limits and PHP's own limits.
 *
 * The single most important rule in this class: callers persist the string that
 * {@see store()} returns, never the `$directory` they passed in. Catbox names
 * every upload itself, so the caller-supplied path is not an identifier.
 */
class CatboxStorage
{
    public function __construct(private readonly string $disk = 'catbox') {}

    /**
     * Host a file and return its permanent public URL.
     *
     * @param  string  $directory  Kept only for logging context; Catbox is a flat
     *                             namespace and has no directories. Passing a
     *                             meaningful value makes orphaned objects much
     *                             easier to attribute.
     * @param  string|null  $name  Preferred filename. Only its extension matters,
     *                             since Catbox discards the name itself.
     */
    public function store(UploadedFile|string $source, string $directory = '', ?string $name = null): string
    {
        if ($source instanceof UploadedFile && ! $source->isValid()) {
            throw CatboxUploadFailed::rejected('the upload did not complete successfully');
        }

        $extension = $this->extensionFor($source, $name);

        $this->context($directory, $extension);

        return $this->adapter()->upload($source, $extension);
    }

    /**
     * Host a file that already lives on a local disk.
     *
     * Used for generated files such as report exports, which are written to disk
     * first because Excel and dompdf both need a real filesystem path.
     */
    public function storeFromDisk(string $path, string $disk = 'local', string $directory = ''): string
    {
        $absolute = Storage::disk($disk)->path($path);

        return $this->store($absolute, $directory, basename($path));
    }

    /**
     * Resolve a stored value to a URL, tolerating legacy `storage/...` paths.
     *
     * This is what every view should call instead of `asset('storage/'.$path)`
     * or `Storage::url($path)`, so that a column holding either shape renders.
     */
    public function url(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($this->isRemote($value)) {
            return trim($value);
        }

        // Resolved by the adapter rather than Laravel's url(), which only knows
        // how to build URLs for local and S3 drivers.
        return $this->adapter()->url($value);
    }

    /**
     * Whether the value is already a fully-qualified URL (i.e. already hosted).
     */
    public function isRemote(?string $value): bool
    {
        return is_string($value) && preg_match('#^https?://\S+$#i', trim($value)) === 1;
    }

    /**
     * Unlink an asset from the application.
     *
     * Deliberately does not remove the remote object — see
     * {@see CatboxAdapter::delete()}.
     */
    public function forget(?string $value): void
    {
        if (blank($value)) {
            return;
        }

        try {
            $this->filesystem()->delete($value);
        } catch (\Throwable $e) {
            // Forgetting a reference must never break the surrounding delete.
            report($e);
        }
    }

    public function size(?string $value): int
    {
        if (blank($value)) {
            return 0;
        }

        try {
            return $this->filesystem()->size($value);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function adapter(): CatboxAdapter
    {
        $driver = $this->filesystem();

        // Storage::disk() returns Laravel's wrapper; the Flysystem adapter sits
        // behind it and is what actually talks to Catbox.
        $adapter = $driver instanceof LaravelFilesystemAdapter
            ? $driver->getAdapter()
            : $driver;

        if (! $adapter instanceof CatboxAdapter) {
            throw new \LogicException(
                "The [{$this->disk}] disk is not backed by CatboxAdapter; check config/filesystems.php and that StorageServiceProvider is registered."
            );
        }

        return $adapter;
    }

    private function filesystem(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    private function extensionFor(UploadedFile|string $source, ?string $name): string
    {
        if ($name !== null && $name !== '') {
            $fromName = pathinfo($name, PATHINFO_EXTENSION);

            if ($fromName !== '') {
                return $fromName;
            }
        }

        if ($source instanceof UploadedFile) {
            // Content-derived extension wins over the client-supplied one.
            //
            // The stored extension decides how the host serves the object, so
            // trusting getClientOriginalExtension() here is what allowed a
            // byte-valid PNG (or PDF) uploaded as "payload.html" to be published as
            // text/html. The scanner's allowlist is checked against the same
            // content-derived value, so the two now agree by construction.
            return $source->guessExtension() ?: ($source->getClientOriginalExtension() ?: 'bin');
        }

        return pathinfo(parse_url($source, PHP_URL_PATH) ?: $source, PATHINFO_EXTENSION) ?: 'bin';
    }

    /**
     * Record which feature requested the upload.
     *
     * Catbox keeps objects forever and the app cannot delete them, so the origin
     * is the only way to attribute an upload — or an orphan — later on.
     */
    private function context(string $directory, string $extension): void
    {
        if ($directory === '') {
            return;
        }

        Log::debug('Catbox upload requested.', [
            'origin' => trim($directory, '/'),
            'extension' => $extension,
        ]);
    }
}
