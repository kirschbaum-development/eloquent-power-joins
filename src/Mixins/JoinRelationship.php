<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class JoinRelationship
{
    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    public static $joinRelationshipCache = [];

    public function joinRelationship()
    {
        return function ($relationName, $callback = null, $joinType = 'join') {
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

    public function leftJoinRelationship()
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

                if ($relation instanceof BelongsToMany) {
                    throw new Exception('Joining nested relationships with BelongsToMany currently is not implemented');
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
