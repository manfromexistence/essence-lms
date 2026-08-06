<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    protected $fillable = [
        'title', 'slug', 'icon', 'short_description', 'description',
        'image', 'price', 'compare_price', 'badge', 'features', 'faqs',
        'is_active', 'is_featured', 'display_order',
    ];

    protected $casts = [
        'features' => 'array',
        'faqs' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $service) {
            if (empty($service->slug) && !empty($service->title)) {
                $service->slug = Str::slug($service->title);
            }
            $original = $service->slug;
            $i = 2;
            while (static::where('slug', $service->slug)->where('id', '!=', $service->id ?? 0)->exists()) {
                $service->slug = $original . '-' . $i++;
            }
        });
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
    public function scopeOrdered($q) { return $q->orderBy('display_order')->orderBy('id'); }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) return $this->image;
            return asset('storage/' . ltrim($this->image, '/'));
        }
        return 'https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=1200&q=82';
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->compare_price && $this->compare_price > $this->price && $this->price > 0) {
            return (int) round((1 - $this->price / $this->compare_price) * 100);
        }
        return null;
    }

    public function getRouteKeyName(): string { return 'slug'; }
}
