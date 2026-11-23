<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MemberActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type_id',
        'location_id',
        'amenity_id',
        'event_id',
        'activity_time',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'activity_time' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function pointLog(): HasOne
    {
        return $this->hasOne(PointLog::class);
    }

    /**
     * Exclude activities marked void in metadata.
     */
    public function scopeNotVoided($query)
    {
        return $query->whereRaw(
            "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.status')), '') <> 'void'"
        );
    }
}
