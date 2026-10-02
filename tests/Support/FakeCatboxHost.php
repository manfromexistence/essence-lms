<?php

namespace Tests\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Test double for the Catbox media host.
 *
 * Media is hosted on a third-party HTTP service, so tests must not reach the
 * network. This fakes Catbox at the HTTP layer — the same seam the production
 * adapter uses — which keeps the adapter's real multipart building, response
 * parsing and error handling under test rather than stubbing them out.
 *
 * Objects are written to a temporary directory so reads, sizes and MIME types
 * behave like the real thing.
 */
class FakeCatboxHost
{
    /** @var array<string, array{path: string, type: string}> */
    private array $objects = [];

    private string $root;

    private bool $reachable = true;

    /** Set when a failure should be simulated, for error-path tests. */
    private ?string $rejection = null;

    public function __construct()
    {
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'catbox-fake-'.Str::random(8);
    }

    /**
     * Install the fake and return it, so a test can assert on what was uploaded.
     *
     * Scoped to the media host's own URLs: a blanket Http::fake() would also
     * intercept the application's other outbound calls (Brevo, SMS gateways) and
     * answer them with a Catbox response, which is a confusing way to fail.
     */
    public static function install(): self
    {
        $fake = new self;

        Http::fake([
            '*/user/api.php' => fn (Request $request) => $fake->handle($request),
            'files.catbox.moe/*' => fn (Request $request) => $fake->handle($request),
        ]);

        return $fake;
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * @return array<int, string> URLs the application believed it uploaded.
     */
    public function uploadedUrls(): array
    {
        return array_keys($this->objects);
    }

    public function count(): int
    {
        return count($this->objects);
    }

    /**
     * Make the host unreachable, to exercise the transport error path.
     */
    public function unreachable(): self
    {
        $this->reachable = false;

        return $this;
    }

    /**
     * Make the host answer with Catbox's bare-word rejection message.
     */
    public function rejectWith(string $message): self
    {
        $this->rejection = $message;

        return $this;
    }

    /**
     * Remove everything written during the test.
     */
    public function cleanUp(): void
    {
        foreach ($this->objects as $object) {
            @unlink($object['path']);
        }

        $this->objects = [];

        if (is_dir($this->root)) {
            @rmdir($this->root);
        }
    }

    /**
     * @return Response
     */
    private function handle(Request $request)
    {
        if (! $this->reachable) {
            // Mirrors a real connection failure closely enough for the adapter's
            // ConnectionException branch.
            throw new ConnectionException('Simulated network failure.');
        }

        if ($request->method() === 'HEAD') {
            return $this->headResponse($request->url());
        }

        if ($request->method() === 'GET') {
            return $this->getResponse($request->url());
        }

        return $this->uploadResponse($request);
    }

    private function uploadResponse(Request $request)
    {
        if ($this->rejection !== null) {
            // Catbox answers a refused upload with a 412 and a bare phrase.
            return Http::response($this->rejection, 412);
        }

        $body = $request->body();
        $name = $this->filenameFromMultipart($body);

        if ($name === null) {
            return Http::response('No files given.', 412);
        }

        // A tiny but valid PNG, used when the caller uploaded an image.
        $payload = str_contains($name, 'png')
            ? base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
            : 'catbox-fake-payload';

        $path = $this->root.DIRECTORY_SEPARATOR.$name;
        @mkdir($this->root, 0777, true);
        file_put_contents($path, $payload);

        $url = 'https://files.catbox.moe/'.$name;
        $this->objects[$url] = ['path' => $path, 'type' => 'application/octet-stream'];

        return Http::response($url, 200);
    }

    private function headResponse(string $url)
    {
        $object = $this->find($url);

        if ($object === null) {
            return Http::response('', 404);
        }

        return Http::response('', 200, [
            'Content-Length' => (string) filesize($object['path']),
            'Content-Type' => $object['type'],
            'Last-Modified' => gmdate('D, d M Y H:i:s', (int) filemtime($object['path'])).' GMT',
        ]);
    }

    private function getResponse(string $url)
    {
        $object = $this->find($url);

        return $object === null
            ? Http::response('', 404)
            : Http::response(file_get_contents($object['path']), 200);
    }

    /**
     * @return array{path: string, type: string}|null The stored object, or null.
     */
    private function find(string $url): ?array
    {
        return $this->objects[$url] ?? null;
    }

    /**
     * Recover the attachment's filename from a multipart body.
     *
     * Guzzle rewinds the stream before sending, so the raw body is available on
     * the faked request; pulling the filename out of it is enough for the fake
     * to stand in for Catbox's own randomiser.
     */
    private function filenameFromMultipart(string $body): ?string
    {
        if (preg_match('/filename="([^"]+)"/', $body, $m) !== 1) {
            return null;
        }

        return Str::random(16).'.'.pathinfo($m[1], PATHINFO_EXTENSION);
    }
}
