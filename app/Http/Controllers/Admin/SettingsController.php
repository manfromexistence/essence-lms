<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {
    }

    /**
     * Display all settings grouped by category.
     */
    public function index(): View
    {
        $settings = $this->settingsService->getAllGrouped();
        $groups = $this->settingsService->getGroups();

        return view('dashboard.settings.index', compact('settings', 'groups'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Institution Settings
            'institution_name' => 'nullable|string|max:255',
            'institution_logo' => 'nullable|string|max:500',
            'institution_logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'institution_logo_url' => 'nullable|url|max:500',
            'institution_favicon' => 'nullable|string|max:500',
            'institution_favicon_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,ico|max:2048',
            'institution_favicon_url' => 'nullable|url|max:500',
            'institution_address' => 'nullable|string|max:1000',
            'institution_phone' => 'nullable|string|max:20',
            'institution_email' => 'nullable|email|max:255',
            'institution_website' => 'nullable|url|max:255',

            // Student Settings
            'student_id_format' => 'nullable|string|max:100',
            'student_id_sequence_start' => 'nullable|integer|min:1',

            // SMS Settings
            'sms_gateway_enabled' => 'nullable|boolean',
            'sms_gateway_provider' => 'nullable|string|in:twilio,nexmo,custom',
            'sms_gateway_api_key' => 'nullable|string|max:255',
            'sms_gateway_api_secret' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:20',

            // Email (Brevo) Settings
            'brevo_api_key' => 'nullable|string|max:255',
            'brevo_sender_email' => 'nullable|email|max:255',
            'brevo_sender_name' => 'nullable|string|max:255',

            // Attendance Settings
            'attendance_threshold' => 'nullable|integer|min:0|max:100',

            // Payment Settings
            'currency' => 'nullable|string|max:10',
            'receipt_prefix' => 'nullable|string|max:10',
            'invoice_prefix' => 'nullable|string|max:10',

            // Theme Settings
            'theme_primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_primary_foreground' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'theme_secondary_foreground' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'institution_logo_file.image' => 'The logo must be an image file.',
            'institution_logo_file.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif, or webp.',
            'institution_logo_file.max' => 'The logo file size must not exceed 30MB.',
            'institution_favicon_file.image' => 'The favicon must be an image file.',
            'institution_favicon_file.mimes' => 'The favicon must be a file of type: jpeg, png, jpg, gif, webp, or ico.',
            'institution_favicon_file.max' => 'The favicon file size must not exceed 30MB.',
        ]);

        try {
            // Handle logo file upload
            if ($request->hasFile('institution_logo_file')) {
                $logoPath = $request->file('institution_logo_file')->store('logos', 'public');
                $this->settingsService->set('institution_logo', $logoPath);
            } elseif ($request->filled('institution_logo_url') && !str_contains($request->input('institution_logo_url'), '/logo.png')) {
                // Only save URL if it's not the default logo
                $this->settingsService->set('institution_logo', $request->input('institution_logo_url'));
            }

            // Handle favicon file upload
            if ($request->hasFile('institution_favicon_file')) {
                $faviconPath = $request->file('institution_favicon_file')->store('favicons', 'public');
                $this->settingsService->set('institution_favicon', $faviconPath);
            } elseif ($request->filled('institution_favicon_url') && !str_contains($request->input('institution_favicon_url'), '/favicon.ico')) {
                // Only save URL if it's not the default favicon
                $this->settingsService->set('institution_favicon', $request->input('institution_favicon_url'));
            }

            foreach ($validated as $key => $value) {
                // Skip file and URL fields as they're handled above
                if (in_array($key, ['institution_logo_file', 'institution_logo_url', 'institution_favicon_file', 'institution_favicon_url'])) {
                    continue;
                }
                
                if ($value !== null) {
                    $this->settingsService->set($key, $value);
                }
            }

            // Handle checkbox for sms_gateway_enabled (unchecked = not in request)
            if (!$request->has('sms_gateway_enabled')) {
                $this->settingsService->set('sms_gateway_enabled', false);
            }

            // Pre-fill Brevo defaults so the email dashboard works out of the box
            if (!$this->settingsService->get('brevo_sender_email')) {
                $this->settingsService->set('brevo_sender_email', 'ajju40959@gmail.com');
            }
            if (!$this->settingsService->get('brevo_sender_name')) {
                $this->settingsService->set('brevo_sender_name', 'Dhaka IT Institute');
            }

            return redirect()->route('dashboard.settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('dashboard.settings.index')
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Update form field configuration.
     */
    public function updateFormFields(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.visible' => 'required|boolean',
            'fields.*.required' => 'required|boolean',
            'fields.*.order' => 'required|integer|min:1',
        ]);

        try {
            $this->settingsService->set('student_form_fields', $validated['fields']);

            return redirect()->route('dashboard.settings.index')
                ->with('success', 'Form fields configuration updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('dashboard.settings.index')
                ->with('error', 'Failed to update form fields: ' . $e->getMessage());
        }
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): RedirectResponse
    {
        $this->settingsService->clearCache();

        return redirect()->route('dashboard.settings.index')
            ->with('success', 'Settings cache cleared successfully.');
    }
}
