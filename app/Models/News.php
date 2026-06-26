<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    public const TYPE_NEWS = 'news';

    public const TYPE_NOTICE = 'notice';

    protected $fillable = [
        'title',
        'type',
        'content',
        'cover_image',
        'publish_time',
        'is_top',
    ];

    protected function casts(): array
    {
        return [
            'publish_time' => 'datetime',
            'is_top' => 'boolean',
        ];
    }

    public function isNotice(): bool
    {
        return $this->type === self::TYPE_NOTICE;
    }

    public function typeText(): string
    {
        return $this->type === self::TYPE_NOTICE ? '首页公告' : '园区动态';
    }
}
