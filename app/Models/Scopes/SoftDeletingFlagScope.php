<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SoftDeletingFlagScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $column = $model->getQualifiedIsDeleteColumn();
        // 旧数据可能尚未回填 is_delete，NULL 视为未删除
        $builder->where(function (Builder $query) use ($column) {
            $query->where($column, 0)->orWhereNull($column);
        });
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withTrashed', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyTrashed', function (Builder $builder) {
            /** @var Model $model */
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedIsDeleteColumn(), 1);
        });

        $builder->macro('withoutTrashed', function (Builder $builder) {
            /** @var Model $model */
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedIsDeleteColumn(), 0);
        });
    }
}
