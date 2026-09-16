<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function isHappeningNow(): bool
    {
        $now = now();

        return $this->start_date->lte($now) && $this->end_date->gte($now);
    }

    public function isUpcoming(): bool
    {
        return $this->start_date->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->end_date->isPast();
    }

    public function isFull(): bool
    {
        if (! $this->max_participants || $this->max_participants <= 0) {
            return false;
        }

        $count = $this->registrations_count ?? $this->registrations()->count();

        return $count >= $this->max_participants;
    }

    public function isAcceptingRegistrations(): bool
    {
        return $this->status === 'published'
            && ! $this->hasEnded()
            && ! $this->isFull();
    }

    public function scheduleKey(): string
    {
        if ($this->isHappeningNow()) {
            return 'happening';
        }

        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        return 'ended';
    }

    public function scheduleLabel(): string
    {
        return match ($this->scheduleKey()) {
            'happening' => __('Happening now'),
            'upcoming' => __('Upcoming'),
            default => __('Ended'),
        };
    }

    public function scheduleBadgeClasses(): string
    {
        return match ($this->scheduleKey()) {
            'happening' => 'bg-emerald-100 border border-emerald-500 text-emerald-800',
            'upcoming' => 'bg-orange-100 border border-orange-400 text-orange-800',
            default => 'bg-gray-100 border border-gray-400 text-gray-700',
        };
    }

    public function formattedSchedule(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('d M Y').' · '.$this->start_date->format('g:i A').' – '.$this->end_date->format('g:i A');
        }

        return $this->start_date->format('d M Y, g:i A').' – '.$this->end_date->format('d M Y, g:i A');
    }

    /**
     * Location profile preview: happening now, then upcoming (soonest first), then recently ended.
     */
    public function scopeForLocationProfile(Builder $query, int $limit = 5): Builder
    {
        $now = now();

        return $query
            ->with('media')
            ->withCount([
                'registrations',
                'registrations as attended_count' => fn ($registrations) => $registrations->where('status', 'attended'),
            ])
            ->orderByRaw('CASE WHEN start_date <= ? AND end_date >= ? THEN 0 WHEN start_date > ? THEN 1 ELSE 2 END', [$now, $now, $now])
            ->orderByRaw('CASE WHEN start_date > ? THEN start_date END ASC', [$now])
            ->orderByDesc('start_date')
            ->limit($limit);
    }
}
