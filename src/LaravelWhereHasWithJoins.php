<?php

namespace KirschbaumDevelopment\LaravelWhereHasWithJoins;

use Closure;
use RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaravelWhereHasWithJoins
{
    public static function registerEloquentMacros()
    {
        Builder::macro('whereHasWithJoins', function ($relation, Closure $callback = null) {
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

            $relation->performJoinForWhereHasWithJoins($this);

            if (is_callable($callback)) {
                $callback($this);
            }

            return $this;
        });

        HasMany::macro('performJoinForWhereHasWithJoins', function ($builder) {
            $builder->join(
                $this->query->getModel()->getTable(),
                $this->foreignKey,
                '=',
                $this->parent->getTable().'.'.$this->localKey
            );
        });
    }
}
