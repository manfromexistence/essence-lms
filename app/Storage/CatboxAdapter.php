<?php

namespace App\Storage;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use League\Flysystem\CorruptedPathDetected;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\Flysystem\WhitespacePathNormalizer;

/**
 * Flysystem adapter for the Catbox.moe file host.
 *
 * Catbox is an anonymous, append-only HTTP file host. It has no buckets, no
 * directories, no rename and no listing API: it accepts a multipart upload and
 * answers with the public URL of a file it named itself. Flysystem's contract of
 * "you choose the path, the path is the identifier" therefore cannot hold, so
 * this adapter treats the absolute Catbox URL as the canonical identifier and
 * records the mapping when a write had to pick a name on the caller's behalf.
 *
 * Consequences callers must understand:
 *
 *  - Reading requires a full `https://files.catbox.moe/...` URL. A bare name is
 *    resolved against the configured base URL.
 *  - `write()` cannot report the URL Catbox chose, because the interface returns
 *    void. The mapping is kept for the request so that `CatboxStorage::store()`
 *    can hand the real URL back. Always persist what `store()` returns, never
 *    the path that was passed in.
 *  - `delete()` only drops our reference. Catbox honours deletion only for
 *    files uploaded under an account key, and this deployment is deliberately
 *    keyless, so the remote object is intentionally orphaned.
 *  - Operations Catbox genuinely cannot perform throw, so that no caller is left
 *    believing a directory listing or a rename actually happened.
 */
class CatboxAdapter implements FilesystemAdapter
{
    /** Catbox names every upload itself, so the local name only carries the extension. */
    private const NAME_BYTES = 16;

    /**
     * Request-local map of "logical path" => "real Catbox URL".
     *
     * @var array<string, string>
     */
    private array $uploaded = [];

    public function __construct(
        private readonly array $config,
        private readonly WhitespacePathNormalizer $pathNormalizer = new WhitespacePathNormalizer,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Writes
    |--------------------------------------------------------------------------
    */

    /**
     * Upload a stream and return the public URL Catbox assigned to it.
     *
     * This is the single place the adapter talks to the upload endpoint; the
     * interface's own `write*` methods funnel into it.
     *
     * @param  resource  $stream
     */
    public function uploadStream($stream, string $extension = ''): string
    {
        if (! is_resource($stream)) {
            throw UnableToWriteFile::atLocation($extension, 'The upload stream is not a valid resource.');
        }

        return $this->post((string) $extension, $stream, null);
    }

    /**
     * Upload an uploaded file, a local path, or raw contents; return the URL.
     *
     * A URL passed in as the source is treated as already-hosted and returned
     * unchanged, which makes the method safe to call idempotently.
     */
    public function upload(UploadedFile|string $source, ?string $extension = null): string
    {
        if ($source instanceof UploadedFile) {
            if (! $source->isValid()) {
                throw UnableToWriteFile::atLocation('upload', 'The upload did not complete successfully.');
            }

            $path = $source->getRealPath();
            if ($path === false || ! is_readable($path)) {
                throw UnableToWriteFile::atLocation('upload', 'The uploaded file is not readable.');
            }

            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw UnableToWriteFile::atLocation('upload', 'Unable to open the uploaded file.');
            }

            try {
                $ext = $extension
                    ?? ($source->getClientOriginalExtension() ?: $source->guessExtension() ?: '');

                return $this->post($ext, $handle, $source->getMimeType());
            } finally {
                fclose($handle);
            }
        }

        if (preg_match('#^https?://\S+$#i', $source) === 1) {
            return trim($source);
        }

        $handle = fopen($source, 'rb');
        if ($handle === false) {
            throw UnableToWriteFile::atLocation(basename($source), 'The source file is not readable.');
        }

        try {
            return $this->post($extension ?? pathinfo($source, PATHINFO_EXTENSION), $handle, null);
        } finally {
            fclose($handle);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        // The caller has handed us bytes, not a file, so the write goes through
        // a temporary stream rather than through upload()'s path handling.
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw UnableToWriteFile::atLocation($path, 'Unable to open a temporary stream.');
        }

        try {
            fwrite($stream, $contents);
            rewind($stream);

            $this->remember($path, $this->uploadStream($stream, pathinfo($this->resolve($path), PATHINFO_EXTENSION)));
        } finally {
            fclose($stream);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->remember($path, $this->uploadStream($contents, pathinfo($this->resolve($path), PATHINFO_EXTENSION)));
    }

    /*
    |--------------------------------------------------------------------------
    | Reads
    |--------------------------------------------------------------------------
    */

    public function read(string $path): string
    {
        $body = $this->get($this->resolve($path), false);

        if ($body === null) {
            throw UnableToReadFile::fromLocation($path, 'The file could not be read from Catbox.');
        }

        return $body;
    }

    public function readStream(string $path)
    {
        $stream = $this->get($this->resolve($path), true);

        if ($stream === null) {
            throw UnableToReadFile::fromLocation($path, 'The file could not be read from Catbox.');
        }

        return $stream;
    }

    public function fileExists(string $path): bool
    {
        return $this->metadata($this->resolve($path)) !== null;
    }

    public function mimeType(string $path): FileAttributes
    {
        $headers = $this->metadata($this->resolve($path)) ?? [];

        return $this->attributes($path, mimeType: $this->header($headers, 'content-type', 'application/octet-stream'));
    }

    public function fileSize(string $path): FileAttributes
    {
        $headers = $this->metadata($this->resolve($path)) ?? [];

        return $this->attributes($path, fileSize: (int) $this->header($headers, 'content-length', '0'));
    }

    public function lastModified(string $path): FileAttributes
    {
        $headers = $this->metadata($this->resolve($path)) ?? [];

        return $this->attributes(
            $path,
            lastModified: (int) strtotime($this->header($headers, 'last-modified', '')) ?: 0,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Metadata Catbox cannot express
    |--------------------------------------------------------------------------
    */

    /**
     * Every object on Catbox is world-readable, so this is not negotiable.
     *
     * The call is accepted rather than faked: the application stores only
     * Catbox URLs, so treating the value as public is the only truthful answer.
     */
    public function visibility(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function setVisibility(string $path, string $visibility): void {}

    public function directoryExists(string $directory): bool
    {
        // Catbox has no directories; paths are flat and every file is a URL.
        return false;
    }

    public function createDirectory(string $path, Config $config): void {}

    public function listContents(string $path, bool $deep): iterable
    {
        throw UnableToListContents::atLocation(
            $path,
            $deep,
            new RuntimeException('Catbox exposes no index or search operation.')
        );
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw UnableToMoveFile::because(
            'Catbox cannot rename files; upload the asset again instead.',
            $source,
            $destination
        );
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw UnableToCopyFile::because(
            'Catbox cannot copy files; re-upload the source instead.',
            $source,
            $destination
        );
    }

    /**
     * Forget the asset.
     *
     * This is the whole of "deleting" here: the application stops referencing
     * the object and the remote copy is left in place on purpose.
     */
    public function delete(string $path): void
    {
        $url = $this->resolve($path);

        unset($this->uploaded[$this->normalize($path)]);

        Log::info('Catbox asset unlinked; the remote copy is intentionally retained.', [
            'url' => $url,
        ]);
    }

    public function deleteDirectory(string $path): void
    {
        throw UnableToDeleteDirectory::atLocation($path, 'Catbox has no directories to delete.');
    }

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    */

    /**
     * Turn a stored path into a fetchable URL.
     *
     * Absolute URLs pass through untouched, which is what lets the same column hold
     * legacy `storage/...` paths and Catbox URLs side by side.
     */
    public function resolve(string $path): string
    {
        if (self::isAbsoluteUrl($path)) {
            return trim($path);
        }

        $normalized = ltrim($this->normalize($path), '/');
        $mapped = $this->uploaded[$normalized] ?? null;

        return $mapped ?? rtrim((string) $this->config['base_url'], '/').'/'.$normalized;
    }

    /**
     * The URL for a stored path, as Laravel's `url()` helper expects it.
     */
    public function url(string $path): string
    {
        return $this->resolve($path);
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */

    /**
     * Perform the multipart upload and return the URL from the response body.
     *
     * Uses Laravel's HTTP client rather than raw cURL so that uploads are
     * interceptable with Http::fake() in tests and participate in the framework's
     * request logging.
     *
     * @param  resource  $handle
     */
    private function post(string $extension, $handle, ?string $mimeType): string
    {
        $filename = Str::random(self::NAME_BYTES).'.'.$this->normalizeExtension($extension, $mimeType);

        $fields = ['reqtype' => 'fileupload'];
        if (($userhash = trim((string) ($this->config['userhash'] ?? ''))) !== '') {
            $fields['userhash'] = $userhash;
        }

        // Guzzle addresses the attachment by stream resource, so an in-memory
        // stream can be sent directly; a caller-supplied file handle is also
        // fine. Only a stream with no readable representation needs spilling.
        [$source, $spill] = $this->sourceForUpload($handle);

        try {
            $response = $this->client()
                ->attach('fileToUpload', $source, $filename, [
                    'Content-Type' => $mimeType ?: 'application/octet-stream',
                ])
                ->post($this->endpoint(), $fields);
        } catch (ConnectionException $e) {
            throw CatboxUploadFailed::transport($e->getMessage());
        } finally {
            // Only the spill file is ours to remove; a caller-supplied path is not.
            if ($spill !== null) {
                if (is_resource($source)) {
                    fclose($source);
                }

                @unlink($spill);
            }
        }

        $url = trim($response->body());

        // Catbox answers with the URL as plain text on success and with a bare
        // phrase such as "No files given." on failure — sometimes with a 200.
        if ($response->failed() || preg_match('#^https?://\S+$#i', $url) !== 1) {
            throw CatboxUploadFailed::rejected(
                $url !== '' ? $url : "HTTP {$response->status()}",
                $response->status(),
            );
        }

        return $url;
    }

    /**
     * A handle for the upload, plus the spill file to clean up afterwards (null
     * when the caller supplied the handle and owns its own file).
     *
     * @param  resource  $handle
     * @return array{0: resource, 1: string|null}
     */
    private function sourceForUpload($handle): array
    {
        $uri = stream_get_meta_data($handle)['uri'] ?? null;

        if (is_string($uri) && is_file($uri) && is_readable($uri)) {
            return [$handle, null];
        }

        $position = ftell($handle);
        rewind($handle);

        $spill = tempnam(sys_get_temp_dir(), 'catbox-');

        if ($spill === false) {
            throw UnableToWriteFile::atLocation('upload', 'Unable to create a temporary file for the upload.');
        }

        $target = fopen($spill, 'wb');

        if ($target === false || stream_copy_to_stream($handle, $target) === false) {
            is_resource($target) && fclose($target);
            @unlink($spill);

            throw UnableToWriteFile::atLocation('upload', 'Unable to stage the upload contents.');
        }

        fclose($target);

        // Leave the caller's stream where it was found.
        if ($position !== false) {
            fseek($handle, $position);
        }

        $spilled = fopen($spill, 'rb');

        if ($spilled === false) {
            @unlink($spill);

            throw UnableToWriteFile::atLocation('upload', 'Unable to stage the upload contents.');
        }

        return [$spilled, $spill];
    }

    /**
     * Fetch an object back from the host.
     *
     * Streams to a temporary file when a stream is requested, so that reading a
     * large object does not pull the whole thing into memory.
     *
     * @return string|resource|null
     */
    private function get(string $url, bool $asStream)
    {
        $sink = null;

        try {
            if ($asStream) {
                $sink = tempnam(sys_get_temp_dir(), 'catbox-read-');

                if ($sink === false) {
                    return null;
                }

                $response = $this->client()->withOptions(['sink' => $sink])->get($url);
            } else {
                $response = $this->client()->get($url);
            }
        } catch (ConnectionException $e) {
            Log::warning('Catbox read failed.', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Catbox read failed.', ['url' => $url, 'status' => $response->status()]);

            if ($asStream && $sink !== null) {
                @unlink($sink);
            }

            return null;
        }

        if (! $asStream) {
            return $response->body();
        }

        $handle = fopen((string) $sink, 'rb');
        @unlink((string) $sink);

        return $handle === false ? null : $handle;
    }

    /**
     * Catbox has no metadata endpoint, so a HEAD request stands in for one.
     *
     * @return array<string, string>|null Lower-cased response headers, or null.
     */
    private function metadata(string $url): ?array
    {
        try {
            $response = $this->client()->head($url);
        } catch (ConnectionException $e) {
            Log::warning('Catbox metadata request failed.', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        // Symfony preserves the casing the server sent ("Content-Length"), so
        // keys are normalised here to keep the lookups below case-insensitive.
        $headers = [];

        foreach ($response->headers() as $name => $values) {
            $headers[strtolower((string) $name)] = is_array($values)
                ? (string) reset($values)
                : (string) $values;
        }

        return $headers;
    }

    /**
     * The HTTP client used for every request to the media host.
     *
     * Timeouts are generous because a full-size lecture upload is the worst
     * case and the host cannot resume a partial transfer.
     */
    private function client(): PendingRequest
    {
        return Http::withHeaders($this->requestHeaders())
            ->withOptions([
                'timeout' => (int) $this->config['timeout'],
                'connect_timeout' => (int) $this->config['connect_timeout'],
                'allow_redirects' => true,
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function requestHeaders(): array
    {
        return ['User-Agent: '.(string) ($this->config['user_agent'] ?: 'Laravel-LMS')];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, string>  $headers
     */
    private function header(array $headers, string $key, string $default): string
    {
        return trim($headers[$key] ?? '') !== '' ? $headers[$key] : $default;
    }

    private function attributes(
        string $path,
        ?int $fileSize = null,
        ?int $lastModified = null,
        ?string $mimeType = null,
    ): FileAttributes {
        // Catbox objects are world-readable, so public is the only truthful
        // visibility this adapter can report.
        return new FileAttributes(
            $this->normalize($path),
            $fileSize,
            Visibility::PUBLIC,
            $lastModified,
            $mimeType,
        );
    }

    private function normalize(string $path): string
    {
        // An absolute URL is an identifier, not a path. Handing one to the
        // path normaliser would collapse "http://" into "http:/" and silently
        // break every Catbox-backed read, so URLs bypass it entirely.
        if (self::isAbsoluteUrl($path)) {
            return trim($path);
        }

        try {
            return $this->pathNormalizer->normalizePath($path);
        } catch (CorruptedPathDetected) {
            return ltrim($path, '/');
        }
    }

    private static function isAbsoluteUrl(string $value): bool
    {
        return preg_match('#^https?://\S+$#i', trim($value)) === 1;
    }

    private function remember(string $path, string $url): void
    {
        $this->uploaded[$this->normalize($path)] = $url;
    }

    /**
     * Keep an extension Catbox will honour when serving the object back.
     *
     * The filename itself is discarded by Catbox, so this only affects the
     * Content-Type a browser later sees.
     */
    private function normalizeExtension(string $extension, ?string $mimeType = null): string
    {
        $extension = strtolower(trim($extension, ". \t\n\r\0\x0B"));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';

        if ($extension !== '' && strlen($extension) <= 8) {
            return $extension;
        }

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            default => 'bin',
        };
    }

    private function endpoint(): string
    {
        return rtrim((string) $this->config['endpoint'], '/');
    }
}
