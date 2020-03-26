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
        return function ($relation, $callback = null, $joinType = 'join') {
            if (Str::contains($relation, '.')) {
                return $this->joinNestedRelationship($relation, $callback, $joinType);
            }

            $relation = $this->getModel()->{$relation}();
            $relation->performJoinForEloquentPowerJoins($this, $joinType, $callback);

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

    public function joinNestedRelationship()
    {
        return function ($relations, $callback = null, $joinType = 'join') {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relationName) {
                if (! $latestRelation) {
                    $currentModel = $this->getModel();
                    $relation = $currentModel->{$relationName}();
                    $relationModel = $relation->getModel();
                } else {
                    $currentModel = $latestRelation->getModel();
                    $relation = $currentModel->{$relationName}();
                    $relationModel = $relation->getModel();
                }

                if (isset(JoinRelationship::$joinRelationshipCache[spl_object_hash($this)][$relationName])) {
                    $latestRelation = $relation;
                    continue;
                }

                if ($relation instanceof BelongsToMany) {
                    throw new Exception('Joining nested relationships with BelongsToMany currently is not implemented');
                }

                $this->{$joinType}($relationModel->getTable(), function ($join) use ($relation, $relationName, $relationModel, $currentModel, $callback) {
                    $join->on(
                        sprintf('%s.%s', $relationModel->getTable(), $relation->getForeignKeyName()),
                        '=',
                        $currentModel->getQualifiedKeyName()
                    );

                    if ($relation instanceof MorphOneOrMany) {
                        $join->where($relation->getMorphType(), '=', get_class($currentModel));
                    }

                    if ($callback && is_array($callback) && isset($callback[$relationName])) {
                        $callback[$relationName]($join);
                    }
                });

                $latestRelation = $relation;
                JoinRelationship::$joinRelationshipCache[spl_object_hash($this)][$relationName] = true;
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
}
