<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'title',
        'level',
        'content',
        'cover_image',
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
