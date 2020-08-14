<?php

namespace Kirschbaum\EloquentPowerJoins\Mixins;

use Closure;
use Illuminate\Support\Str;

class QueryRelationshipExistence
{
    /**
     * Same as Laravel 'has`, but using joins instead of where exists.
     */
    public function powerJoinHas()
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

            $relation->performJoinForEloquentPowerJoins($this, 'leftPowerJoin', $callback);
            $relation->performHavingForEloquentPowerJoins($this, $operator, $count);

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

                $relation->performJoinForEloquentPowerJoins($this, 'leftPowerJoin', $callback);

                if (count($relations) === ($index + 1)) {
                    $relation->performHavingForEloquentPowerJoins($this, $operator, $count);
                }

                $latestRelation = $relation;
            }

            return $this;
        };
    }

    public function powerJoinDoesntHave()
    {
        return function ($relation, $boolean = 'and', Closure $callback = null) {
            return $this->powerJoinHas($relation, '<', 1, $boolean, $callback);
        };
    }

    public function getSelect()
    {
        return function () {
            return $this->getQuery()->getSelect();
        };
    }

    public function wherepowerJoinHas()
    {
        return function ($relation, Closure $callback = null, $operator = '>=', $count = 1) {
            return $this->powerJoinHas($relation, $operator, $count, 'and', $callback);
        };
    }

    public function getGroupBy()
    {
        return function () {
            return $this->getQuery()->getGroupBy();
        };
    }
}
