<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Location extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'is_active',
        'location_code',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function memberActivities(): HasMany
    {
        return $this->hasMany(MemberActivity::class);
    }

    public function pointLogs(): HasMany
    {
        return $this->hasMany(PointLog::class);
    }

    /**
     * Register media collections for location thumbnails
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get the thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('thumbnail');

        return $media ? $media->getUrl() : null;
    }

    public function formattedAddress(): string
    {
        return trim(implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ])));
    }

    public function mapThumbnailUrl(int $width = 400, int $height = 250): ?string
    {
        $apiKey = config('services.google_maps.api_key');

        if (! $apiKey) {
            return null;
        }

        if ($this->latitude && $this->longitude) {
            $lat = $this->latitude;
            $lng = $this->longitude;

            return "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size={$width}x{$height}&markers=color:red|{$lat},{$lng}&key={$apiKey}";
        }

        $address = $this->formattedAddress();

        if ($address === '') {
            return null;
        }

        $encoded = urlencode($address);

        return "https://maps.googleapis.com/maps/api/staticmap?center={$encoded}&zoom=15&size={$width}x{$height}&markers=color:red|{$encoded}&key={$apiKey}";
    }

    public function coverImageUrl(int $width = 400, int $height = 250): ?string
    {
        return $this->thumbnail_url ?: $this->mapThumbnailUrl($width, $height);
    }

    public function displayStatus(): string
    {
        if ($this->trashed()) {
            return __('Archived');
        }

        return $this->is_active ? __('Active') : __('Inactive');
    }

    public function statusBadgeClasses(): string
    {
        if ($this->trashed()) {
            return 'bg-red-200 border border-red-400 text-red-800';
        }

        return $this->is_active
            ? 'bg-green-200 border border-green-400 text-green-800'
            : 'bg-gray-200 border border-gray-400 text-gray-800';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($location) {
            if (empty($location->location_code)) {
                $location->location_code = static::generateUniqueLocationCode();
            }
        });
    }

    /**
     * Generate a unique location code.
     */
    protected static function generateUniqueLocationCode(): string
    {
        do {
            $code = 'LOC-'.strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (static::where('location_code', $code)->exists());

        return $code;
    }
}
