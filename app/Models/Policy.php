<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\SoftDeleteFlag;

class Policy extends Model
{
    use SoftDeleteFlag;

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
