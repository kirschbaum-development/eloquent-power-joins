<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use KirschbaumDevelopment\EloquentJoins\PowerJoinClause;

class JoinRelationship
{
    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    public static $joinRelationshipCache = [];

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
    public function joinRelationship()
    {
        return function ($relationName, $callback = null, $joinType = 'join', $useAlias = false) {
            $joinType = JoinRelationship::$joinMethodsMap[$joinType] ?? $joinType;

            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (Str::contains($relationName, '.')) {
                return $this->joinNestedRelationship($relationName, $callback, $joinType, $useAlias);
            }

            if ($this->relationshipAlreadyJoined($relationName)) {
                return $this;
            }

            $relation = $this->getModel()->{$relationName}();
            $alias = $useAlias ? $this->generateAliasForRelationship($relation, $relationName) : null;
            $relation->performJoinForEloquentPowerJoins($this, $joinType, $callback, $alias);

            $this->markRelationshipAsAlreadyJoined($relationName);
            $this->clearPowerJoinCaches();

            return $this;
        };
    }

    /**
     * Join the relationship(s) using table aliases.
     */
    public function joinRelationshipUsingAlias()
    {
        return function ($relationName, $callback = null) {
            return $this->joinRelationship($relationName, $callback, 'join', true);
        };
    }

    /**
     * Left join the relationship(s) using table aliases.
     */
    public function leftJoinRelationshipUsingAlias()
    {
        return function ($relationName, $callback = null) {
            return $this->joinRelationship($relationName, $callback, 'leftJoin', true);
        };
    }

    /**
     * Right join the relationship(s) using table aliases.
     */
    public function rightJoinRelationshipUsingAlias()
    {
        return function ($relationName, $callback = null) {
            return $this->joinRelationship($relationName, $callback, 'rightJoin', true);
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
        return function ($relation, $callback = null, $useAlias = false) {
            return $this->joinRelationship($relation, $callback, 'leftJoin', $useAlias);
        };
    }

    public function leftJoinRelation()
    {
        return function ($relation, $callback = null, $useAlias = false) {
            return $this->joinRelationship($relation, $callback, 'leftJoin', $useAlias);
        };
    }

    public function rightJoinRelationship()
    {
        return function ($relation, $callback = null, $useAlias = false) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias);
        };
    }

    public function rightJoinRelation()
    {
        return function ($relation, $callback = null, $useAlias = false) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias);
        };
    }

    /**
     * Join nested relationships.
     */
    public function joinNestedRelationship()
    {
        return function ($relations, $callback = null, $joinType = 'join', $useAlias = false) {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relationName) {
                $currentModel = $latestRelation ? $latestRelation->getModel() : $this->getModel();
                $relation = $currentModel->{$relationName}();
                $alias = $useAlias ? $this->generateAliasForRelationship($relation, $relationName) : null;
                $relationCallback = null;

                if ($useAlias) {
                    $this->cachePowerJoinAlias($relation->getModel(), $alias);
                }

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
                    $relationCallback,
                    $alias
                );

                $latestRelation = $relation;
                $this->markRelationshipAsAlreadyJoined($relationName);
            }

            $this->clearPowerJoinCaches();

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

            $latestRelationshipModel = array_reduce($relationships, function ($model, $relationshipName) {
                return $model->$relationshipName()->getModel();
            }, $this->getModel());

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
            return isset(JoinRelationship::$joinRelationshipCache[spl_object_id($this)][$relation]);
        };
    }

    /**
     * Marks the relationship as already joined.
     */
    public function markRelationshipAsAlreadyJoined()
    {
        return function ($relation) {
            JoinRelationship::$joinRelationshipCache[spl_object_id($this)][$relation] = true;
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function powerJoin()
    {
        return function ($table, $first, $operator = null, $second = null, $type = 'inner', $where = false) {
            $model = $operator instanceof Model ? $operator : null;
            $join = $this->newPowerJoinClause($this->query, $type, $table, $model);

            // If the first "column" of the join is really a Closure instance the developer
            // is trying to build a join with a complex "on" clause containing more than
            // one condition, so we'll add the join and call a Closure with the query.
            if ($first instanceof Closure) {
                $first($join);

                $this->query->joins[] = $join;

                $this->query->addBinding($join->getBindings(), 'join');
            }

            // If the column is simply a string, we can assume the join simply has a basic
            // "on" clause with a single condition. So we will just build the join with
            // this simple join clauses attached to it. There is not a join callback.
            else {
                $method = $where ? 'where' : 'on';

                $this->query->joins[] = $join->$method($first, $operator, $second);

                $this->query->addBinding($join->getBindings(), 'join');
            }

            return $this;
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function leftPowerJoin()
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'left');
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function rightPowerJoin()
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'right');
        };
    }

    public function newPowerJoinClause()
    {
        return function (QueryBuilder $parentQuery, $type, $table, Model $model = null) {
            return new PowerJoinClause($parentQuery, $type, $table, $model);
        };
    }

    public function generateAliasForRelationship()
    {
        return function ($relation, $relationName) {
            if ($relation instanceof BelongsToMany || $relation instanceof HasManyThrough) {
                return [
                    md5($relationName.'table1'.time()),
                    md5($relationName.'table2'.time()),
                ];
            }

            return md5($relationName.time());
        };
    }

    /**
     * Cache the power join table alias used for the power join.
     */
    public function cachePowerJoinAlias()
    {
        return function ($model, $alias) {
            JoinRelationship::$powerJoinAliasesCache[spl_object_id($model)] = $alias;
        };
    }

    /**
     * Clear the power join caches.
     */
    public function clearPowerJoinCaches()
    {
        return function () {
            JoinRelationship::$powerJoinAliasesCache = [];

            return $this;
        };
    }
}
