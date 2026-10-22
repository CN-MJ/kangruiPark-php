<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SoftDeletingFlagScope;

/**
 * 假删除：is_delete=0 正常，is_delete=1 已删除；deleted_at 记录最近一次删除时间。
 */
trait SoftDeleteFlag
{
    public static function bootSoftDeleteFlag(): void
    {
        static::addGlobalScope(new SoftDeletingFlagScope);
    }

    public function initializeSoftDeleteFlag(): void
    {
        $this->casts = array_merge($this->casts ?? [], [
            'is_delete' => 'integer',
            'deleted_at' => 'datetime',
        ]);
    }

    public function getIsDeleteColumn(): string
    {
        return 'is_delete';
    }

    public function getQualifiedIsDeleteColumn(): string
    {
        return $this->qualifyColumn($this->getIsDeleteColumn());
    }

    public function getDeletedAtColumn(): string
    {
        return 'deleted_at';
    }

    public function trashed(): bool
    {
        return (int) $this->{$this->getIsDeleteColumn()} === 1;
    }

    public function restore(): bool
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getIsDeleteColumn()} = 0;
        $result = $this->save();
        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * 真删除（一般不用，假删除请用 delete）
     */
    public function forceDelete(): ?bool
    {
        if ($this->fireModelEvent('forceDeleting') === false) {
            return false;
        }

        $deleted = $this->newModelQuery()
            ->withoutGlobalScope(SoftDeletingFlagScope::class)
            ->where($this->getKeyName(), $this->getKey())
            ->toBase()
            ->delete();

        $this->fireModelEvent('forceDeleted', false);
        $this->exists = false;

        return $deleted > 0 ? true : null;
    }

    protected function performDeleteOnModel(): void
    {
        $query = $this->newModelQuery()
            ->withoutGlobalScope(SoftDeletingFlagScope::class)
            ->where($this->getKeyName(), $this->getKey());

        $time = $this->freshTimestamp();
        $columns = [
            $this->getIsDeleteColumn() => 1,
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
        ];

        if ($this->usesTimestamps()) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->toBase()->update($columns);

        $this->{$this->getIsDeleteColumn()} = 1;
        $this->{$this->getDeletedAtColumn()} = $time;
        $this->syncOriginal();
    }
}
