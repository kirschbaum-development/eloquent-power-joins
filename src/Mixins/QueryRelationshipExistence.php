<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class QueryRelationshipExistence
{
    public function hasUsingJoins()
    {
        return function ($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null) {
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
        };
    }

    public function hasNestedUsingJoins()
    {
        return function ($relations, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null) {
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
        };
    }

    public function doesntHaveUsingJoins()
    {
        return function ($relation, $boolean = 'and', Closure $callback = null) {
            return $this->hasUsingJoins($relation, '<', 1, $boolean, $callback);
        };
    }

    public function getSelect()
    {
        return function () {
            return $this->getQuery()->getSelect();
        };
    }

    public function whereHasUsingJoins()
    {
        return function ($relation, Closure $callback = null, $operator = '>=', $count = 1) {
            return $this->hasUsingJoins($relation, $operator, $count, 'and', $callback);
        };
    }

    public function getGroupBy()
    {
        return function () {
            return $this->getQuery()->getGroupBy();
        };
    }
}
