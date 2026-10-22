<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\SoftDeleteFlag;

class Activity extends Model
{
    use SoftDeleteFlag;

    public const STATUS_OPEN = 0;
    public const STATUS_FINISHED = 1;
    public const STATUS_CANCELLED = 2;

    protected $fillable = [
        'title',
        'cover_image',
        'start_time',
        'end_time',
        'location',
        'description',
        'max_attendees',
        'registered_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_attendees' => 'integer',
            'registered_count' => 'integer',
            'status' => 'integer',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }
}
