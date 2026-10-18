<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkRole extends Model
{
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
