<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        return function ($relationName, $callback = null, $joinType = 'join') {
            $joinType = JoinRelationship::$joinMethodsMap[$joinType] ?? $joinType;

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
}
