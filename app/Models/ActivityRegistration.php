<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\SoftDeleteFlag;

class ActivityRegistration extends Model
{
    use SoftDeleteFlag;

    protected $fillable = [
        'activity_id',
        'contact_name',
        'contact_phone',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
