<?php

namespace Kirschbaum\PowerJoins;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait PowerJoins
{
    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    private $joinRelationshipCache = [];

    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    public static $powerJoinAliasesCache = [];

    /**
     * Join method map.
     */
    public static $joinMethodsMap = [
        'join' => 'powerJoin',
        'leftJoin' => 'leftPowerJoin',
        'rightJoin' => 'rightPowerJoin',
    ];

    /**
     * Join the relationship(s).
     */
    public function scopeJoinRelationship(Builder $query, $relationName, $callback = null, $joinType = 'join', $useAlias = false, bool $disableExtraConditions = false): void
    {
        $joinType = PowerJoins::$joinMethodsMap[$joinType] ?? $joinType;

        if (is_null($query->getSelect())) {
            $query->select(sprintf('%s.*', $query->getModel()->getTable()));
        }

        if (Str::contains($relationName, '.')) {
            $query->joinNestedRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions);

            return;
        }

        $relationQuery = $query->getModel()->{$relationName}()->getQuery();
        $relationJoinCache = "{$relationQuery->getModel()->getTable()}.{$relationName}";

        if ($this->relationshipAlreadyJoined($relationJoinCache)) {
            return;
        }

        $relation = $query->getModel()->{$relationName}();
        $alias = $useAlias ? $this->generateAliasForRelationship($relation, $relationName) : null;
        $relation->performJoinForEloquentPowerJoins($query, $joinType, $callback, $alias, $disableExtraConditions);

        $this->markRelationshipAsAlreadyJoined($relationJoinCache);
        $this->clearPowerJoinCaches();
    }

    /**
     * Join the relationship(s) using table aliases.
     */
    public function scopeJoinRelationshipUsingAlias(Builder $query, $relationName, $callback = null, bool $disableExtraConditions = false): void
    {
        $query->joinRelationship($relationName, $callback, 'join', true, $disableExtraConditions);
    }

    /**
     * Left join the relationship(s) using table aliases.
     */
    public function scopeLeftJoinRelationshipUsingAlias(Builder $query, $relationName, $callback = null, bool $disableExtraConditions = false): void
    {
        $query->joinRelationship($relationName, $callback, 'leftJoin', true, $disableExtraConditions);
    }

    /**
     * Right join the relationship(s) using table aliases.
     */
    public function scopeRightJoinRelationshipUsingAlias(Builder $query, $relationName, $callback = null, bool $disableExtraConditions = false)
    {
        $query->joinRelationship($relationName, $callback, 'rightJoin', true, $disableExtraConditions);
    }

    public function scopeJoinRelation(Builder $query, $relationName, $callback = null, $joinType = 'join', $useAlias = false, bool $disableExtraConditions = false): void
    {
        $query->joinRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions);
    }

    public function scopeLeftJoinRelationship(Builder $query, $relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false)
    {
        $query->joinRelationship($relation, $callback, 'leftJoin', $useAlias, $disableExtraConditions);
    }

    public function scopeLeftJoinRelation(Builder $query, $relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false)
    {
        $query->joinRelationship($relation, $callback, 'leftJoin', $useAlias, $disableExtraConditions);
    }

    public function scopeRightJoinRelationship(Builder $query, $relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false): void
    {
        $query->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions);
    }

    public function scopeRightJoinRelation(Builder $query, $relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false): void
    {
        $query->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions);
    }

    /**
     * Join nested relationships.
     */
    public function scopeJoinNestedRelationship(Builder $query, string $relationships, $callback = null, $joinType = 'join', $useAlias = false, bool $disableExtraConditions = false): void
    {
        $relations = explode('.', $relationships);

        /** @var \Illuminate\Database\Eloquent\Relations\Relation */
        $latestRelation = null;

        foreach ($relations as $index => $relationName) {
            $currentModel = $latestRelation ? $latestRelation->getModel() : $query->getModel();
            $relation = $currentModel->{$relationName}();
            $alias = $useAlias ? $this->generateAliasForRelationship($relation, $relationName) : null;
            $relationCallback = null;
            $relationJoinCache = $index === 0
                ? "{$relation->getQuery()->getModel()->getTable()}.{$index}.{$relationName}"
                : "{$relation->getQuery()->getModel()->getTable()}.{$latestRelation->getModel()->getTable()}.{$relationName}";

            if ($useAlias) {
                $this->cachePowerJoinAlias($relation->getModel(), $alias);
            }

            if ($callback && is_array($callback) && isset($callback[$relationName])) {
                $relationCallback = $callback[$relationName];
            }

            if ($this->relationshipAlreadyJoined($relationJoinCache)) {
                $latestRelation = $relation;

                continue;
            }

            $relation->performJoinForEloquentPowerJoins(
                $query,
                $joinType,
                $relationCallback,
                $alias,
                $disableExtraConditions
            );

            $latestRelation = $relation;
            $this->markRelationshipAsAlreadyJoined($relationJoinCache);
        }

        $this->clearPowerJoinCaches();
    }

    /**
     * Order by a field in the defined relationship.
     */
    public function scopeOrderByPowerJoins(Builder $query, $sort, $direction = 'asc', $aggregation = null, $joinType = 'join'): void
    {
        if (is_array($sort)) {
            $relationships = explode('.', $sort[0]);
            $column = $sort[1];
            $latestRelationshipName = $relationships[count($relationships) - 1];
        } else {
            $relationships = explode('.', $sort);
            $column = array_pop($relationships);
            $latestRelationshipName = $relationships[count($relationships) - 1];
        }

        $query->joinRelationship(implode('.', $relationships), null, $joinType);

        $latestRelationshipModel = array_reduce($relationships, function ($model, $relationshipName) {
            return $model->$relationshipName()->getModel();
        }, $query->getModel());

        if ($aggregation) {
            $query->selectRaw(
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
            if ($column instanceof Expression) {
                // dd($column);
                $query->orderBy($column, $direction);
            } else {
                $query->orderBy(
                    sprintf('%s.%s', $latestRelationshipModel->getTable(), $column),
                    $direction
                );
            }
        }
    }

    public function scopeOrderByLeftPowerJoins(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, null, 'leftJoin');
    }

    /**
     * Order by the COUNT aggregation using joins.
     */
    public function scopeOrderByPowerJoinsCount(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'COUNT');
    }

    public function scopeOrderByLeftPowerJoinsCount(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'COUNT', 'leftJoin');
    }

    /**
     * Order by the SUM aggregation using joins.
     */
    public function scopeOrderByPowerJoinsSum(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'SUM');
    }

    public function scopeOrderByLeftPowerJoinsSum(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'SUM', 'leftJoin');
    }

    /**
     * Order by the AVG aggregation using joins.
     */
    public function scopeOrderByPowerJoinsAvg(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'AVG');
    }

    public function scopeOrderByLeftPowerJoinsAvg(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'AVG', 'leftJoin');
    }

    /**
     * Order by the MIN aggregation using joins.
     */
    public function scopeOrderByPowerJoinsMin(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'MIN');
    }

    public function scopeOrderByLeftPowerJoinsMin(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'MIN', 'leftJoin');
    }

    /**
     * Order by the MAX aggregation using joins.
     */
    public function scopeOrderByPowerJoinsMax(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'MAX');
    }

    public function scopeOrderByLeftPowerJoinsMax(Builder $query, $sort, $direction = 'asc'): void
    {
        $query->orderByPowerJoins($sort, $direction, 'MAX', 'leftJoin');
    }

    /**
     * Same as Laravel 'has`, but using joins instead of where exists.
     */
    public function scopePowerJoinHas(Builder $query, $relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null): void
    {
        if (is_null($query->getSelect())) {
            $query->select(sprintf('%s.*', $query->getModel()->getTable()));
        }

        if (is_null($query->getGroupBy())) {
            $query->groupBy($query->getModel()->getQualifiedKeyName());
        }

        if (is_string($relation)) {
            if (Str::contains($relation, '.')) {
                $query->hasNestedUsingJoins($relation, $operator, $count, 'and', $callback);

                return;
            }

            $relation = $query->getRelationWithoutConstraintsProxy($relation);
        }

        $relation->performJoinForEloquentPowerJoins($query, 'leftPowerJoin', $callback);
        $relation->performHavingForEloquentPowerJoins($query, $operator, $count);
    }

    public function scopeHasNestedUsingJoins(Builder $query, $relations, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        $relations = explode('.', $relations);

        /** @var \Illuminate\Database\Eloquent\Relations\Relation */
        $latestRelation = null;

        foreach ($relations as $index => $relation) {
            if (! $latestRelation) {
                $relation = $query->getRelationWithoutConstraintsProxy($relation);
            } else {
                $relation = $latestRelation->getModel()->query()->getRelationWithoutConstraintsProxy($relation);
            }

            $relation->performJoinForEloquentPowerJoins($query, 'leftPowerJoin', $callback);

            if (count($relations) === ($index + 1)) {
                $relation->performHavingForEloquentPowerJoins($query, $operator, $count);
            }

            $latestRelation = $relation;
        }
    }

    public function scopePowerJoinDoesntHave(Builder $query, $relation, $boolean = 'and', Closure $callback = null): void
    {
        $query->powerJoinHas($relation, '<', 1, $boolean, $callback);
    }

    public function scopePowerJoinWhereHas(Builder $query, $relation, Closure $callback = null, $operator = '>=', $count = 1): void
    {
        $query->powerJoinHas($relation, $operator, $count, 'and', $callback);
    }

    /**
     * Checks if the relationship was already joined.
     */
    public function relationshipAlreadyJoined($relation)
    {
        return isset($this->joinRelationshipCache[spl_object_id($this)][$relation]);
    }

    /**
     * Marks the relationship as already joined.
     */
    public function markRelationshipAsAlreadyJoined($relation)
    {
        $this->joinRelationshipCache[spl_object_id($this)][$relation] = true;
    }

    public function generateAliasForRelationship($relation, $relationName)
    {
        if ($relation instanceof BelongsToMany || $relation instanceof HasManyThrough) {
            return [
                md5($relationName . 'table1' . time()),
                md5($relationName . 'table2' . time()),
            ];
        }

        return md5($relationName . time());
    }

    /**
     * Cache the power join table alias used for the power join.
     */
    public function cachePowerJoinAlias($model, $alias)
    {
        PowerJoins::$powerJoinAliasesCache[spl_object_id($model)] = $alias;
    }

    /**
     * Clear the power join caches.
     */
    public function clearPowerJoinCaches()
    {
        PowerJoins::$powerJoinAliasesCache = [];

        return $this;
    }
}
