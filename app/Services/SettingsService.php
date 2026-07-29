<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * Cache key prefix for settings.
     */
    protected const CACHE_PREFIX = 'settings_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Default settings with their types and groups.
     */
    protected array $defaults = [
        // Student ID Format
        'student_id_format' => [
            'value' => '{YEAR}{BATCH}{SEQ:4}',
            'group' => 'student',
            'type' => 'string',
        ],
        'student_id_sequence_start' => [
            'value' => 1,
            'group' => 'student',
            'type' => 'integer',
        ],

        // Form Configuration
        'student_form_fields' => [
            'value' => [
                'name' => ['visible' => true, 'required' => true, 'order' => 1],
                'email' => ['visible' => true, 'required' => true, 'order' => 2],
                'phone' => ['visible' => true, 'required' => true, 'order' => 3],
                'address' => ['visible' => true, 'required' => false, 'order' => 4],
                'guardian_name' => ['visible' => true, 'required' => false, 'order' => 5],
                'guardian_phone' => ['visible' => true, 'required' => false, 'order' => 6],
                'date_of_birth' => ['visible' => true, 'required' => false, 'order' => 7],
                'blood_group' => ['visible' => true, 'required' => false, 'order' => 8],
                'photo' => ['visible' => true, 'required' => false, 'order' => 9],
            ],
            'group' => 'form',
            'type' => 'json',
        ],

        // SMS Gateway
        'sms_gateway_enabled' => [
            'value' => false,
            'group' => 'sms',
            'type' => 'boolean',
        ],
        'sms_gateway_provider' => [
            'value' => 'twilio',
            'group' => 'sms',
            'type' => 'string',
        ],
        'sms_gateway_api_key' => [
            'value' => '',
            'group' => 'sms',
            'type' => 'string',
        ],
        'sms_gateway_api_secret' => [
            'value' => '',
            'group' => 'sms',
            'type' => 'string',
        ],
        'sms_sender_id' => [
            'value' => '',
            'group' => 'sms',
            'type' => 'string',
        ],

        // Institution Info
        'institution_name' => [
            'value' => 'Dhaka IT Institute',
            'group' => 'institution',
            'type' => 'string',
        ],
        'institution_logo' => [
            'value' => 'images/brand/dhaka-it-institute-logo.png',
            'group' => 'institution',
            'type' => 'string',
        ],
        'institution_favicon' => [
            'value' => 'images/brand/dhaka-it-institute-favicon.png',
            'group' => 'institution',
            'type' => 'string',
        ],
        'institution_address' => [
            'value' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216. Behind Dhaka WASA.',
            'group' => 'institution',
            'type' => 'text',
        ],
        'institution_phone' => [
            'value' => '+8801682715576',
            'group' => 'institution',
            'type' => 'string',
        ],
        'institution_email' => [
            'value' => 'dhakaitinstitute@gmail.com',
            'group' => 'institution',
            'type' => 'string',
        ],
        'institution_website' => [
            'value' => 'https://dhakaitinstitute.com',
            'group' => 'institution',
            'type' => 'string',
        ],

        // Attendance Settings
        'attendance_threshold' => [
            'value' => 75,
            'group' => 'attendance',
            'type' => 'integer',
        ],

        // Payment Settings
        'currency' => [
            'value' => 'BDT',
            'group' => 'payment',
            'type' => 'string',
        ],
        'receipt_prefix' => [
            'value' => 'RCP',
            'group' => 'payment',
            'type' => 'string',
        ],
        'invoice_prefix' => [
            'value' => 'INV',
            'group' => 'payment',
            'type' => 'string',
        ],

        // Theme Settings
        'theme_primary_color' => [
            'value' => '#168536',
            'group' => 'theme',
            'type' => 'string',
        ],
        'theme_primary_foreground' => [
            'value' => '#ffffff',
            'group' => 'theme',
            'type' => 'string',
        ],
        'theme_secondary_color' => [
            'value' => '#171717',
            'group' => 'theme',
            'type' => 'string',
        ],
        'theme_secondary_foreground' => [
            'value' => '#ffffff',
            'group' => 'theme',
            'type' => 'string',
        ],
    ];

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                return $this->castValue($setting->value, $setting->type);
            }

            // Return default from our defaults array if exists
            if (isset($this->defaults[$key])) {
                return $this->defaults[$key]['value'];
            }

            return $default;
        });
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, ?string $group = null, ?string $type = null): Setting
    {
        // Get defaults for this key if they exist
        $defaults = $this->defaults[$key] ?? [];
        $group = $group ?? $defaults['group'] ?? 'general';
        $type = $type ?? $defaults['type'] ?? 'string';

        // Validate the setting before saving
        if (!$this->validateSetting($key, $value, $type)) {
            throw new \InvalidArgumentException("Invalid value for setting: {$key}");
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
            ]
        );

        // Clear cache for this setting
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Get all settings.
     */
    public function getAll(): array
    {
        $settings = Setting::all()->keyBy('key');
        $result = [];

        // Merge with defaults
        foreach ($this->defaults as $key => $default) {
            if ($settings->has($key)) {
                $setting = $settings->get($key);
                $result[$key] = $this->castValue($setting->value, $setting->type);
            } else {
                $result[$key] = $default['value'];
            }
        }

        // Add any settings not in defaults
        foreach ($settings as $key => $setting) {
            if (!isset($result[$key])) {
                $result[$key] = $this->castValue($setting->value, $setting->type);
            }
        }

        return $result;
    }

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group): array
    {
        $settings = Setting::where('group', $group)->get()->keyBy('key');
        $result = [];

        // Get defaults for this group
        foreach ($this->defaults as $key => $default) {
            if ($default['group'] === $group) {
                if ($settings->has($key)) {
                    $setting = $settings->get($key);
                    $result[$key] = $this->castValue($setting->value, $setting->type);
                } else {
                    $result[$key] = $default['value'];
                }
            }
        }

        // Add any settings not in defaults
        foreach ($settings as $key => $setting) {
            if (!isset($result[$key])) {
                $result[$key] = $this->castValue($setting->value, $setting->type);
            }
        }

        return $result;
    }

    /**
     * Get all settings grouped by their group.
     */
    public function getAllGrouped(): array
    {
        $settings = Setting::all();
        $result = [];

        // Initialize with defaults
        foreach ($this->defaults as $key => $default) {
            $group = $default['group'];
            if (!isset($result[$group])) {
                $result[$group] = [];
            }
            $result[$group][$key] = [
                'value' => $default['value'],
                'type' => $default['type'],
            ];
        }

        // Override with actual settings
        foreach ($settings as $setting) {
            $group = $setting->group;
            if (!isset($result[$group])) {
                $result[$group] = [];
            }
            $result[$group][$setting->key] = [
                'value' => $this->castValue($setting->value, $setting->type),
                'type' => $setting->type,
            ];
        }

        return $result;
    }

    /**
     * Validate a setting value based on its type.
     */
    public function validateSetting(string $key, mixed $value, ?string $type = null): bool
    {
        $type = $type ?? $this->defaults[$key]['type'] ?? 'string';

        return match ($type) {
            'integer' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            'json', 'array' => is_array($value) || (is_string($value) && json_decode($value) !== null),
            'string', 'text' => is_string($value) || is_numeric($value),
            default => true,
        };
    }

    /**
     * Get the default value for a setting.
     */
    public function getDefault(string $key): mixed
    {
        return $this->defaults[$key]['value'] ?? null;
    }

    /**
     * Check if a setting has a default value.
     */
    public function hasDefault(string $key): bool
    {
        return isset($this->defaults[$key]);
    }

    /**
     * Get all available groups.
     */
    public function getGroups(): array
    {
        $groups = array_unique(array_column($this->defaults, 'group'));
        $dbGroups = Setting::distinct()->pluck('group')->toArray();

        return array_unique(array_merge($groups, $dbGroups));
    }

    /**
     * Clear all settings cache.
     */
    public function clearCache(): void
    {
        foreach (array_keys($this->defaults) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        // Also clear any settings from database
        $dbKeys = Setting::pluck('key')->toArray();
        foreach ($dbKeys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Cast value based on type.
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => is_array($value) ? $value : json_decode($value, true),
            'text', 'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Get the institution logo URL with fallback to default.
     */
    public function getLogo(): string
    {
        $logo = $this->get('institution_logo', '');
        
        if (empty($logo)) {
            return asset('logo.png');
        }
        
        // If it's a full URL, return as is
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }
        
        // If it's a storage path, convert to asset URL
        if (str_starts_with($logo, 'images/')) {
            return asset($logo);
        }

        return asset('storage/' . $logo);
    }

    /**
     * Get the institution favicon URL with fallback to default.
     */
    public function getFavicon(): string
    {
        $favicon = $this->get('institution_favicon', 'images/brand/dhaka-it-institute-favicon.png');
        
        if (empty($favicon)) {
            return asset('images/brand/dhaka-it-institute-favicon.png') . '?v=20260729';
        }
        
        // If it's a full URL, return as is
        if (str_starts_with($favicon, 'http://') || str_starts_with($favicon, 'https://')) {
            return $favicon;
        }

        // Brand assets live directly under public/, while uploaded icons use storage/.
        if (str_starts_with($favicon, 'images/')) {
            return asset($favicon) . '?v=20260729';
        }
        
        // If it's a storage path, convert to asset URL
        return asset('storage/' . $favicon);
    }
}
