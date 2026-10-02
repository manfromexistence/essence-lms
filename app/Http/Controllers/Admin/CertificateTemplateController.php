<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesHostedMedia;
use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Storage\CatboxStorage;
use Illuminate\Http\Request;

class CertificateTemplateController extends Controller
{
    use HandlesHostedMedia;

    /** Template artwork that may be replaced with a hosted upload. */
    private const ARTWORK_FIELDS = ['background_image', 'logo_image', 'signature_image'];

    public function index()
    {
        $templates = CertificateTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        return view('dashboard.certificates.templates', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'background_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:10240', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'logo_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'signature_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'width' => 'nullable|integer|min:600|max:3000',
            'height' => 'nullable|integer|min:400|max:2000',
            'is_default' => 'nullable|boolean',
        ]);

        $data = array_merge($data, $this->hostTemplateArtwork($request));

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = true;
        $data['width'] = $data['width'] ?? 1200;
        $data['height'] = $data['height'] ?? 900;

        // If this is the default, unset others
        if ($data['is_default']) {
            CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        CertificateTemplate::create($data);

        return back()->with('success', 'Certificate template created.');
    }

    public function edit(CertificateTemplate $template)
    {
        $template->load('certificates');
        return view('dashboard.certificates.edit', compact('template'));
    }

    public function update(Request $request, CertificateTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'background_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:10240', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'logo_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'signature_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120', new \App\Rules\SafeUpload(['jpeg', 'png', 'jpg', 'webp'])],
            'width' => 'nullable|integer|min:600|max:3000',
            'height' => 'nullable|integer|min:400|max:2000',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'layout_config' => 'nullable|json',
        ]);

        $data = array_merge($data, $this->hostTemplateArtwork($request));

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        // Parse layout_config JSON into array (preserve {elements, background_opacity} structure)
        if (!empty($request->input('layout_config'))) {
            $decoded = json_decode($request->input('layout_config'), true);
            if (is_array($decoded)) {
                if (isset($decoded['elements']) && is_array($decoded['elements'])) {
                    $decoded['elements'] = array_values($decoded['elements']);
                }
                $data['layout_config'] = $decoded;
            }
        }

        if ($data['is_default']) {
            CertificateTemplate::where('is_default', true)->where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        $template->update($data);

        return back()->with('success', 'Certificate template updated.');
    }

    public function destroy(CertificateTemplate $template)
    {
        if ($template->is_default) {
            return back()->with('error', 'The default template cannot be deleted. Set another template as default first.');
        }

        if ($template->certificates()->exists()) {
            return back()->with('error', 'This template has issued certificates and cannot be deleted. Deactivate it instead.');
        }

        $this->unlinkMedia(
            $template->background_image,
            $template->logo_image,
            $template->signature_image,
        );

        $template->delete();

        return back()->with('success', 'Certificate template deleted.');
    }

    public function setDefault(CertificateTemplate $template)
    {
        CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        $template->update(['is_default' => true, 'is_active' => true]);

        return back()->with('success', "{$template->name} is now the default template.");
    }

    /**
     * Host whichever template artwork files were submitted.
     *
     * @return array<string, string>  Field => hosted URL, for the fields supplied.
     */
    private function hostTemplateArtwork(Request $request): array
    {
        $hosted = [];

        foreach (self::ARTWORK_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $hosted[$field] = app(CatboxStorage::class)->store(
                    $request->file($field),
                    'certificate-templates',
                    $field
                );
            }
        }

        return $hosted;
    }
}
