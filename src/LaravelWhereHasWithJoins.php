<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins;

use Closure;
use RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaravelWhereHasWithJoins
{
    public static function registerEloquentMacros()
    {
        static::registerHasFunctions();
        static::registerWhereHasFunctions();
    }

    protected static function registerHasFunctions()
    {
        Builder::macro('hasWithJoins', function ($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null) {
            if (is_string($relation)) {
                if (strpos($relation, '.') !== false) {
                    // figure this out
                    // return $this->hasNestedWithJoins($relation, $operator, $count, 'and', $callback);
                }

                $relation = $this->getRelationWithoutConstraints($relation);
            }

            if ($relation instanceof MorphTo) {
                throw new RuntimeException('Please use whereHasMorph() for MorphTo relationships.');
            }

            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (is_null($this->getGroupBy())) {
                $this->groupBy($this->getModel()->getQualifiedKeyName());
            }

            $relation->performJoinForWhereHasWithJoins($this);
            $relation->performHavingForHasWithJoins($this, $operator, $count);

            if (is_callable($callback)) {
                $callback($this);
            }

            return $this;
        });
    }

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

        Builder::macro('whereHasWithJoins', function ($relation, Closure $callback = null, $operator = '>=', $count = 1) {
            return $this->hasWithJoins($relation, $operator, $count, 'and', $callback);
        });

        HasMany::macro('performJoinForWhereHasWithJoins', function ($builder) {
            $builder->join(
                $this->query->getModel()->getTable(),
                $this->foreignKey,
                '=',
                $this->parent->getTable().'.'.$this->localKey
            );
        });

        HasMany::macro('performHavingForHasWithJoins', function ($builder, $operator, $count) {
            $builder
                ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), $this->query->getModel()->getTable()))
                ->havingRaw(sprintf('%s_count %s %d', $this->query->getModel()->getTable(), $operator, $count));
        });
    }
}
