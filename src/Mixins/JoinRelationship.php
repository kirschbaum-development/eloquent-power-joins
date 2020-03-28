<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;

class JoinRelationship
{
    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    public static $joinRelationshipCache = [];

    /**
     * Join the relationship(s).
     */
    public function joinRelationship()
    {
        return function ($relationName, $callback = null, $joinType = 'join') {
            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (Str::contains($relationName, '.')) {
                return $this->joinNestedRelationship($relationName, $callback, $joinType);
            }

            if ($this->relationshipAlreadyJoined($relationName)) {
                return $this;
            }

            $relation = $this->getModel()->{$relationName}();
            $relation->performJoinForEloquentPowerJoins($this, $joinType, $callback);

            $this->markRelationshipAsAlreadyJoined($relationName);

            return $this;
        };
    }

    public function joinRelation()
    {
        return function ($relationName, $callback = null, $joinType = 'join') {
            return $this->joinRelationship($relationName, $callback.$joinType);
        };
    }

    public function leftJoinRelationship()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'leftJoin');
        };
    }

    public function leftJoinRelation()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'leftJoin');
        };
    }

    public function rightJoinRelationship()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'rightJoin');
        };
    }

    public function rightJoinRelation()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'rightJoin');
        };
    }

    /**
     * Join nested relationships.
     */
    public function joinNestedRelationship()
    {
        return function ($relations, $callback = null, $joinType = 'join') {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relationName) {
                $currentModel = $latestRelation ? $latestRelation->getModel() : $this->getModel();
                $relation = $currentModel->{$relationName}();
                $relationCallback = null;

                if ($callback && is_array($callback) && isset($callback[$relationName])) {
                    $relationCallback = $callback[$relationName];
                }

                if ($this->relationshipAlreadyJoined($relationName)) {
                    $latestRelation = $relation;
                    continue;
                }

                $relation->performJoinForEloquentPowerJoins(
                    $this,
                    $joinType,
                    $relationCallback
                );

                $latestRelation = $relation;
                $this->markRelationshipAsAlreadyJoined($relationName);
            }

            return $this;
        };
    }

    /**
     * Order by a field in the defined relationship.
     */
    public function orderByUsingJoins()
    {
        return function ($sort, $direction = 'asc', $aggregation = null) {
            $relationships = explode('.', $sort);
            $column = array_pop($relationships);
            $latestRelationshipName = $relationships[count($relationships) - 1];

            $this->joinRelationship(implode('.', $relationships));

            $latestRelationshipModel = $this->getModel()->$latestRelationshipName()->getModel();

            if ($aggregation) {
                $this->selectRaw(
                    sprintf(
                        '%s(%s.%s) as %s_aggregation',
                        $aggregation,
                        $latestRelationshipModel->getTable(),
                        $column,
                        $latestRelationshipName
                    )
                )
                    ->groupBy(sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()))
                    ->orderBy(sprintf('%s_aggregation', $latestRelationshipName), $direction);
            } else {
                $this->orderBy(sprintf('%s.%s', $latestRelationshipModel->getTable(), $column), $direction);
            }

            return $this;
        };
    }

    /**
     * Order by the COUNT aggregation using joins.
     */
    public function orderByCountUsingJoins()
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByUsingJoins($sort, $direction, 'COUNT');
        };
    }

    /**
     * Order by the SUM aggregation using joins.
     */
    public function orderBySumUsingJoins()
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByUsingJoins($sort, $direction, 'SUM');
        };
    }

    /**
     * Order by the AVG aggregation using joins.
     */
    public function orderByAvgUsingJoins()
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByUsingJoins($sort, $direction, 'AVG');
        };
    }

    /**
     * Order by the MIN aggregation using joins.
     */
    public function orderByMinUsingJoins()
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByUsingJoins($sort, $direction, 'MIN');
        };
    }

    /**
     * Order by the MAX aggregation using joins.
     */
    public function orderByMaxUsingJoins()
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByUsingJoins($sort, $direction, 'MAX');
        };
    }

    /**
     * Checks if the relationship was already joined.
     */
    public function relationshipAlreadyJoined()
    {
        return function ($relation) {
            return isset(JoinRelationship::$joinRelationshipCache[spl_object_hash($this)][$relation]);
        };
    }

    /**
     * Marks the relationship as already joined.
     */
    public function markRelationshipAsAlreadyJoined()
    {
        return function ($relation) {
            JoinRelationship::$joinRelationshipCache[spl_object_hash($this)][$relation] = true;
        };
    }
}
