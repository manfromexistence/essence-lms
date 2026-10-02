<?php

namespace App\Http\Controllers\Concerns;

use App\Services\FileScanService;
use App\Storage\CatboxStorage;
use App\Storage\CatboxUploadFailed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Shared handling for media that the application hosts on Catbox.
 *
 * Two concerns live here because every controller that touches media needs
 * both, and four controllers used to carry their own near-identical copy of the
 * image-input rule.
 *
 * Because media is hosted on Catbox, a stored value is a permanent public URL
 * rather than a path on the local disk. Unlinking is therefore local-only: the
 * reference goes away, the remote object stays. See {@see unlinkMedia()}.
 *
 * @see HandlesHostedMedia::resolveImageInput()
 *      for the reusable image-input component's `{name}_file` / `{name}_url`
 *      field convention.
 */
trait HandlesHostedMedia
{
    /**
     * Resolve an image field to the value that should be persisted.
     *
     * @param  array<int, string>  $allowedExtensions  Extensions accepted by the
     *                                                 content scanner, without dots.
     *
     * @throws ValidationException When the upload is missing, unsafe, or rejected
     *                             by the media host.
     */
    protected function resolveImageInput(
        Request $request,
        string $name,
        string $directory,
        array $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'],
    ): ?string {
        $fileKey = $name.'_file';
        $urlKey = $name.'_url';

        if ($request->hasFile($fileKey)) {
            return $this->storeImageUpload($request, $name, $directory, $allowedExtensions);
        }

        return $this->resolveImageUrl($request, $name, $urlKey);
    }

    /**
     * Upload a single file field to the media host.
     *
     * @param  array<int, string>  $allowedExtensions
     */
    protected function storeImageUpload(
        Request $request,
        string $name,
        string $directory,
        array $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'],
    ): string {
        $file = $request->file($name.'_file');

        if (! $file || ! $file->isValid()) {
            $code = $file?->getError() ?: UPLOAD_ERR_NO_FILE;

            Log::warning('Image upload did not complete.', [
                'field' => $name,
                'error_code' => $code,
            ]);

            throw ValidationException::withMessages([
                $name => $code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE
                    ? 'That image is larger than the server accepts. Please compress it and try again.'
                    : 'That image could not be uploaded. Please try again.',
            ]);
        }

        // Content sniffing before the file ever leaves the server.
        if ($rejection = app(FileScanService::class)->inspect($file, $allowedExtensions)) {
            Log::warning('Image upload rejected by the content scanner.', [
                'field' => $name,
                'original_name' => $file->getClientOriginalName(),
                'reason' => $rejection,
            ]);

            throw ValidationException::withMessages([
                $name => 'That file is not a valid '.$this->humaniseExtensions($allowedExtensions).' image.',
            ]);
        }

        try {
            return app(CatboxStorage::class)->store($file, $directory, $file->getClientOriginalName());
        } catch (CatboxUploadFailed $e) {
            Log::error('Image upload failed at the media host.', [
                'field' => $name,
                'directory' => $directory,
                'reason' => $e->reason,
            ]);

            throw ValidationException::withMessages([$name => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Image upload failed unexpectedly.', [
                'field' => $name,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $name => 'That image could not be stored. Please try again.',
            ]);
        }
    }

    /**
     * Accept a pasted URL in place of an upload.
     *
     * External URLs are stored verbatim: re-hosting someone else's image is
     * neither possible nor desirable, and media_url() already passes them
     * through untouched.
     */
    protected function resolveImageUrl(Request $request, string $name, ?string $urlKey = null): ?string
    {
        $url = trim((string) $request->input($urlKey ?: $name.'_url', ''));

        if ($url === '') {
            return null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
            throw ValidationException::withMessages([
                $name => 'That does not look like a valid image URL.',
            ]);
        }

        return $url;
    }

    /**
     * Drop the application's reference to a media asset.
     *
     * This does not remove anything from Catbox — that needs an account key the
     * institute does not use — so the remote object is intentionally left in
     * place. The application stops pointing at it, which is what callers
     * actually mean by "delete".
     *
     * External URLs supplied by an administrator are left entirely alone: they
     * belong to someone else.
     */
    protected function unlinkMedia(?string ...$values): void
    {
        $storage = app(CatboxStorage::class);

        foreach ($values as $value) {
            if (blank($value) || $storage->isRemote($value)) {
                continue;
            }

            $storage->forget($value);
        }
    }

    /**
     * @param  array<int, string>  $extensions
     */
    private function humaniseExtensions(array $extensions): string
    {
        $upper = array_map(strtoupper(...), $extensions);
        $last = array_pop($upper);

        return $upper === [] ? (string) $last : implode(', ', $upper).' or '.$last;
    }
}
