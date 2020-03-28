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
        return function ($sort) {
            $relationships = explode('.', $sort);
            $sort = array_pop($relationships);
            $lastRelationship = $relationships[count($relationships) - 1];

            $this->joinRelationship(implode('.', $relationships));

            tap(array_pop($relationships), function ($latestRelation) use ($sort) {
                $table = $this->getModel()->$latestRelation()->getModel()->getTable();
                $this->orderBy(sprintf('%s.%s', $table, $sort));
            });

            return $this;
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
