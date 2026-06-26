<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    protected $fillable = [
        'username',
        'password',
        'name',
    ];

    protected $hidden = [
        'password',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(AdminToken::class);
    }
}
