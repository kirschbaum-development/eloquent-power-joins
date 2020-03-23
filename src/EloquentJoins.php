<?php

namespace KirschbaumDevelopment\EloquentJoins;

use Closure;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class EloquentJoins
{
    /**
     * Register macros with Eloquent.
     */
    public static function registerEloquentMacros()
    {
        static::registerJoinRelationshipFunctions();
        static::registerHasFunctions();
        static::registerWhereHasFunctions();
    }

    /**
     * Register 'has' methods.
     */
    protected static function registerHasFunctions()
    {
        Builder::macro('hasUsingJoins', function ($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null) {
            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (is_null($this->getGroupBy())) {
                $this->groupBy($this->getModel()->getQualifiedKeyName());
            }

            if (is_string($relation)) {
                if (Str::contains($relation, '.')) {
                    return $this->hasNestedUsingJoins($relation, $operator, $count, 'and', $callback);
                }

                $relation = $this->getRelationWithoutConstraints($relation);
            }

            $relation->performJoinForWhereHasUsingJoins($this);
            $relation->performHavingForHasUsingJoins($this, $operator, $count);

            if (is_callable($callback)) {
                $callback($this);
            }

            return $this;
        });

        Builder::macro('hasNestedUsingJoins', function ($relations, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null) {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relation) {
                if (! $latestRelation) {
                    $relation = $this->getRelationWithoutConstraints($relation);
                } else {
                    $relation = $latestRelation->getModel()->query()->getRelationWithoutConstraints($relation);
                }

                $relation->performJoinForWhereHasUsingJoins($this, $latestRelation);

                if (count($relations) === ($index + 1)) {
                    $relation->performHavingForHasUsingJoins($this, $operator, $count);
                }

                $latestRelation = $relation;
            }

            return $this;
        });

        Builder::macro('doesntHaveUsingJoins', function ($relation, $boolean = 'and', Closure $callback = null) {
            return $this->hasUsingJoins($relation, '<', 1, $boolean, $callback);
        });

        Builder::macro('whereDoesntHaveUsingJoins', function ($relation, Closure $callback = null) {
            throw new RuntimeException('This is not implemented yet');
        });
    }

    /**
     * Register 'whereHas' methods.
     */
    protected static function registerWhereHasFunctions()
    {
        QueryBuilder::macro('getGroupBy', function () {
            return $this->groups;
        });

        Builder::macro('getGroupBy', function () {
            return $this->getQuery()->getGroupBy();
        });

        QueryBuilder::macro('getSelect', function () {
            return $this->columns;
        });

        Builder::macro('getSelect', function () {
            return $this->getQuery()->getSelect();
        });

        Builder::macro('whereHasUsingJoins', function ($relation, Closure $callback = null, $operator = '>=', $count = 1) {
            return $this->hasUsingJoins($relation, $operator, $count, 'and', $callback);
        });

        HasMany::macro('performJoinForWhereHasUsingJoins', function ($builder, $previousRelation = null) {
            $builder->leftJoin(
                $this->query->getModel()->getTable(),
                $this->foreignKey,
                '=',
                $this->parent->getTable().'.'.$this->localKey
            );
        });

        HasMany::macro('performHavingForHasUsingJoins', function ($builder, $operator, $count) {
            $builder
                ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), $this->query->getModel()->getTable()))
                ->havingRaw(sprintf('%s_count %s %d', $this->query->getModel()->getTable(), $operator, $count));
        });
    }

    /**
     * Register 'joinRelationship' methods.
     */
    protected static function registerJoinRelationshipFunctions()
    {
        Builder::macro('joinRelationship', function ($relation, $joinType = 'join') {
            if (Str::contains($relation, '.')) {
                return $this->joinNestedRelationship($relation, $joinType);
            }

            $relation = $this->getModel()->{$relation}();

            $this->{$joinType}($relation->getModel()->getTable(), function ($join) use ($relation) {
                $join->on(
                    sprintf('%s.%s', $relation->getModel()->getTable(), $relation->getForeignKeyName()),
                    '=',
                    $this->getModel()->getQualifiedKeyName()
                );

                if ($relation instanceof MorphOneOrMany) {
                    $join->where($relation->getMorphType(), '=', get_class($this->getModel()));
                }
            });

            return $this;
        });

        Builder::macro('leftJoinRelationship', function ($relation) {
            return $this->joinRelationship($relation, 'leftJoin');
        });

        Builder::macro('rightJoinRelationship', function ($relation) {
            return $this->joinRelationship($relation, 'rightJoin');
        });

        Builder::macro('joinNestedRelationship', function ($relations, $joinType = 'join') {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relation) {
                if (! $latestRelation) {
                    $currentModel = $this->getModel();
                    $relation = $currentModel->{$relation}();
                    $relationModel = $relation->getModel();
                } else {
                    $currentModel = $latestRelation->getModel();
                    $relation = $currentModel->{$relation}();
                    $relationModel = $relation->getModel();
                }

                $this->{$joinType}($relationModel->getTable(), function ($join) use ($relation, $relationModel, $currentModel) {
                    $join->on(
                        sprintf('%s.%s', $relationModel->getTable(), $relation->getForeignKeyName()),
                        '=',
                        $currentModel->getQualifiedKeyName()
                    );

                    if ($relation instanceof MorphOneOrMany) {
                        $join->where($relation->getMorphType(), '=', get_class($currentModel));
                    }
                });

                $latestRelation = $relation;
            }

            return $this;
        });
    }
}
