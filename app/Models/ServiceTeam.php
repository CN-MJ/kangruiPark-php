<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTeam extends Model
{
    protected $fillable = [
        'name',
        'title',
        'phone',
        'photo',
        'sort_order',
        'is_show',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_show' => 'boolean',
        ];
    }
}
