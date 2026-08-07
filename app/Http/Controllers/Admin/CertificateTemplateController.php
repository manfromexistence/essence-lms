<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateController extends Controller
{
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
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'signature_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'width' => 'nullable|integer|min:600|max:3000',
            'height' => 'nullable|integer|min:400|max:2000',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->hasFile('background_image')) {
            $data['background_image'] = $request->file('background_image')->store('certificate-templates', 'public');
        }
        if ($request->hasFile('logo_image')) {
            $data['logo_image'] = $request->file('logo_image')->store('certificate-templates', 'public');
        }
        if ($request->hasFile('signature_image')) {
            $data['signature_image'] = $request->file('signature_image')->store('certificate-templates', 'public');
        }

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

    public function update(Request $request, CertificateTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'signature_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'width' => 'nullable|integer|min:600|max:3000',
            'height' => 'nullable|integer|min:400|max:2000',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->hasFile('background_image')) {
            $data['background_image'] = $request->file('background_image')->store('certificate-templates', 'public');
        }
        if ($request->hasFile('logo_image')) {
            $data['logo_image'] = $request->file('logo_image')->store('certificate-templates', 'public');
        }
        if ($request->hasFile('signature_image')) {
            $data['signature_image'] = $request->file('signature_image')->store('certificate-templates', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            CertificateTemplate::where('is_default', true)->where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        $template->update($data);

        return back()->with('success', 'Certificate template updated.');
    }

    public function destroy(CertificateTemplate $template)
    {
        foreach (['background_image', 'logo_image', 'signature_image'] as $field) {
            if ($template->{$field} && Storage::disk('public')->exists($template->{$field})) {
                Storage::disk('public')->delete($template->{$field});
            }
        }
        $template->delete();

        return back()->with('success', 'Certificate template deleted.');
    }

    public function setDefault(CertificateTemplate $template)
    {
        CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        $template->update(['is_default' => true, 'is_active' => true]);

        return back()->with('success', "{$template->name} is now the default template.");
    }
}
