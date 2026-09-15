<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'location_id',
        'created_by',
        'title',
        'description',
        'start_date',
        'end_date',
        'venue',
        'max_participants',
        'status',
        'event_code',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        // Event registrations are linked via event_registrations.event_id.
        // Note: event_registrations.type is used to describe registration source (app/qr/manual),
        // not whether the record is for an Event vs Program.
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->event_code)) {
                $event->event_code = static::generateUniqueEventCode();
            }
        });
    }

    /**
     * Generate a unique event code for QR code generation.
     */
    protected static function generateUniqueEventCode(): string
    {
        do {
            $code = 'EVT-'.strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (static::where('event_code', $code)->exists());

        return $code;
    }

    /**
     * Register media collections for event images
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

    public function displayStatus(): string
    {
        if ($this->trashed()) {
            return __('Deleted');
        }

        return ucfirst((string) $this->status);
    }

    public function statusBadgeClasses(): string
    {
        if ($this->trashed()) {
            return 'bg-red-200 border border-red-400 text-red-800';
        }

        return match ($this->status) {
            'published' => 'bg-green-200 border border-green-400 text-green-800',
            'cancelled' => 'bg-red-200 border border-red-400 text-red-800',
            'completed' => 'bg-gray-200 border border-gray-400 text-gray-800',
            default => 'bg-yellow-200 border border-yellow-400 text-yellow-800',
        };
    }
}
