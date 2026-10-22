<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\SoftDeleteFlag;

class ServiceTeam extends Model
{
    use SoftDeleteFlag;

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
