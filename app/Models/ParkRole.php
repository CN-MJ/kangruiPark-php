<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\SoftDeleteFlag;

class ParkRole extends Model
{
    use SoftDeleteFlag;

    protected $fillable = [
        'key',
        'name',
        'permissions',
        'is_system',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }
}
