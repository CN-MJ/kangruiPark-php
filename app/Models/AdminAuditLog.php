<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * 管理操作留痕：只允许新增与查询，禁止修改/删除。
 */
class AdminAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'action',
        'target_type',
        'target_id',
        'payload',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('日志不可修改');
        });

        static::deleting(function () {
            throw new LogicException('日志不可删除');
        });
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('日志不可修改');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('日志不可删除');
    }

    public function forceDelete(): ?bool
    {
        throw new LogicException('日志不可删除');
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('日志不可修改');
    }
}
