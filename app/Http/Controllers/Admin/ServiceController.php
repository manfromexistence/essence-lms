<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Service::ordered();
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(fn($qq) => $qq->where('title','like',"%{$s}%")->orWhere('slug','like',"%{$s}%"));
        }
        $services = $q->paginate(12)->withQueryString();
        return view('dashboard.services.index', compact('services'));
    }

    public function create()
    {
        return view('dashboard.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'icon' => 'nullable|string|max:50',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:5000',
            'image_file' => 'nullable|image|max:20480',
            'image_url' => 'nullable|url|max:1000',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'badge' => 'nullable|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'faqs' => 'nullable|array',
            'faqs.*.q' => 'required_with:faqs|string|max:255',
            'faqs.*.a' => 'required_with:faqs|string|max:1000',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $image = $this->handleImage($request);
        if ($image) $data['image'] = $image;
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['features'] = array_values(array_filter($data['features'] ?? []));
        if (isset($data['faqs'])) {
            $data['faqs'] = array_values(array_filter($data['faqs'], fn($f) => !empty($f['q'])));
        }
        unset($data['image_file'], $data['image_url']);

        Service::create($data);
        return redirect()->route('dashboard.services.index')->with('success','Service created.');
    }

    public function edit(Service $service)
    {
        return view('dashboard.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug,'.$service->id,
            'icon' => 'nullable|string|max:50',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:5000',
            'image_file' => 'nullable|image|max:20480',
            'image_url' => 'nullable|url|max:1000',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'badge' => 'nullable|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'faqs' => 'nullable|array',
            'faqs.*.q' => 'required_with:faqs|string|max:255',
            'faqs.*.a' => 'required_with:faqs|string|max:1000',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $image = $this->handleImage($request);
        if ($image) {
            if ($service->image && !filter_var($service->image, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $image;
        }
        if (!empty($data['slug'])) $data['slug'] = Str::slug($data['slug']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['features'] = array_values(array_filter($data['features'] ?? []));
        if (isset($data['faqs'])) {
            $data['faqs'] = array_values(array_filter($data['faqs'], fn($f) => !empty($f['q'])));
        }
        unset($data['image_file'], $data['image_url']);

        $service->update($data);
        return redirect()->route('dashboard.services.index')->with('success','Service updated.');
    }

    public function destroy(Service $service)
    {
        if ($service->image && !filter_var($service->image, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }
        $service->delete();
        return back()->with('success','Service deleted.');
    }

    private function handleImage(Request $request): ?string
    {
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            return $request->file('image_file')->store('services', 'public');
        }
        if ($request->filled('image_url') && filter_var($request->input('image_url'), FILTER_VALIDATE_URL)) {
            return $request->input('image_url');
        }
        return null;
    }
}
