<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Kirschbaum\PowerJoins\JoinsHelper;
use Kirschbaum\PowerJoins\PowerJoinClause;
use Kirschbaum\PowerJoins\StaticCache;

/**
 * @mixin Builder
 * @method \Illuminate\Database\Eloquent\Model getModel()
 * @property \Illuminate\Database\Eloquent\Builder $query
 */
class JoinRelationship
{
    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function powerJoin(): Closure
    {
        return function ($table, $first, $operator = null, $second = null, $type = 'inner', $where = false): static {
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
    public function leftPowerJoin(): Closure
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'left');
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function rightPowerJoin(): Closure
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'right');
        };
    }

    public function newPowerJoinClause(): Closure
    {
        return function (QueryBuilder $parentQuery, $type, $table, Model $model = null) {
            return new PowerJoinClause($parentQuery, $type, $table, $model);
        };
    }

    /**
     * Join the relationship(s).
     */
    public function joinRelationship(): Closure
    {
        return function (
            $relationName,
            $callback = null,
            $joinType = 'join',
            $useAlias = false,
            bool $disableExtraConditions = false
        ) {
            $joinType = JoinsHelper::$joinMethodsMap[$joinType] ?? $joinType;
            $useAlias = is_string($callback) ? false : $useAlias;
            $joinHelper = JoinsHelper::make($this->getModel());
            $callback = $joinHelper->formatJoinCallback($callback);

            $this->getQuery()->beforeQuery(function () use ($joinHelper) {
                $joinHelper->clear();
            });

            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (Str::contains($relationName, '.')) {
                $this->joinNestedRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions);

                return $this;
            }

            $relation = $this->getModel()->{$relationName}();
            $relationQuery = $relation->getQuery();
            $alias = $joinHelper->getAliasName($useAlias, $relation, $relationName,
                $relationQuery->getModel()->getTable(), $callback);

            if ($relation instanceof BelongsToMany && !is_array($alias)) {
                $extraAlias = $joinHelper->getAliasName($useAlias, $relation, $relationName,
                    $relation->getTable(),
                    $callback);
                $alias = [$extraAlias, $alias];
            }

            $aliasString = is_array($alias) ? implode('.', $alias) : $alias;

            $relationJoinCache = $alias
                ? "{$aliasString}.{$relationQuery->getModel()->getTable()}.{$relationName}"
                : "{$relationQuery->getModel()->getTable()}.{$relationName}";

            if ($joinHelper->relationshipAlreadyJoined($this->getModel(), $relationJoinCache)) {
                return $this;
            }


            $joinHelper->markRelationshipAsAlreadyJoined($this->getModel(), $relationJoinCache);
            StaticCache::clear();

            $relation->performJoinForEloquentPowerJoins(
                builder: $this,
                joinType: $joinType,
                callback: $callback,
                alias: $alias,
                disableExtraConditions: $disableExtraConditions
            );

            return $this;

        };
    }

    /**
     * Join the relationship(s) using table aliases.
     */
    public function joinRelationshipUsingAlias(): Closure
    {
        return function ($relationName, $callback = null, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relationName, $callback, 'join', true, $disableExtraConditions);
        };
    }

    /**
     * Left join the relationship(s) using table aliases.
     */
    public function leftJoinRelationshipUsingAlias(): Closure
    {
        return function ($relationName, $callback = null, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relationName, $callback, 'leftJoin', true, $disableExtraConditions);
        };
    }

    /**
     * Right join the relationship(s) using table aliases.
     */
    public function rightJoinRelationshipUsingAlias(): Closure
    {
        return function ($relationName, $callback = null, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relationName, $callback, 'rightJoin', true, $disableExtraConditions);
        };
    }

    public function joinRelation(): Closure
    {
        return function (
            $relationName,
            $callback = null,
            $joinType = 'join',
            $useAlias = false,
            bool $disableExtraConditions = false
        ) {
            return $this->joinRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions);
        };
    }

    public function leftJoinRelationship(): Closure
    {
        return function ($relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relation, $callback, 'leftJoin', $useAlias, $disableExtraConditions);
        };
    }

    public function leftJoinRelation(): Closure
    {
        return function ($relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relation, $callback, 'leftJoin', $useAlias, $disableExtraConditions);
        };
    }

    public function rightJoinRelationship(): Closure
    {
        return function ($relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions);
        };
    }

    public function rightJoinRelation(): Closure
    {
        return function ($relation, $callback = null, $useAlias = false, bool $disableExtraConditions = false) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions);
        };
    }

    /**
     * Join nested relationships.
     */
    public function joinNestedRelationship(): Closure
    {
        return function (
            string $relationships,
            $callback = null,
            $joinType = 'join',
            $useAlias = false,
            bool $disableExtraConditions = false
        ) {
            $relations = explode('.', $relationships);
            $joinHelper = JoinsHelper::make($this->getModel());
            /** @var Relation */
            $latestRelation = null;

            $part = [];
            foreach ($relations as $relationName) {
                $part[] = $relationName;
                $fullRelationName = join(".", $part);

                $currentModel = $latestRelation ? $latestRelation->getModel() : $this->getModel();
                $relation = $currentModel->{$relationName}();
                $relationCallback = null;

                if ($callback && is_array($callback) && isset($callback[$relationName])) {
                    $relationCallback = $callback[$relationName];
                }

                if ($callback && is_array($callback) && isset($callback[$fullRelationName])) {
                    $relationCallback = $callback[$fullRelationName];
                }

                $alias = $joinHelper->getAliasName($useAlias, $relation, $relationName,
                    $relation->getQuery()->getModel()->getTable(), $relationCallback);
                if ($alias && $relation instanceof BelongsToMany && !is_array($alias)) {
                    $extraAlias = $joinHelper->getAliasName($useAlias, $relation, $relationName, $relation->getTable(),
                        $relationCallback);
                    $alias = [$extraAlias, $alias];
                }

                $aliasString = is_array($alias) ? implode('.', $alias) : $alias;
                $useAlias = $alias ? true : $useAlias;

                if ($alias) {
                    $relationJoinCache = $latestRelation
                        ? "{$aliasString}.{$relation->getQuery()->getModel()->getTable()}.{$latestRelation->getModel()->getTable()}.{$relationName}"
                        : "{$aliasString}.{$relation->getQuery()->getModel()->getTable()}.{$relationName}";
                } else {
                    $relationJoinCache = $latestRelation
                        ? "{$relation->getQuery()->getModel()->getTable()}.{$latestRelation->getModel()->getTable()}.{$relationName}"
                        : "{$relation->getQuery()->getModel()->getTable()}.{$relationName}";
                }

                if ($useAlias) {
                    StaticCache::setTableAliasForModel($relation->getModel(), $alias);
                }


                if ($joinHelper->relationshipAlreadyJoined($this->getModel(), $relationJoinCache)) {
                    $latestRelation = $relation;

                    continue;
                }

                $relation->performJoinForEloquentPowerJoins(
                    $this,
                    $joinType,
                    $relationCallback,
                    $alias,
                    $disableExtraConditions
                );

                $latestRelation = $relation;
                $joinHelper->markRelationshipAsAlreadyJoined($this->getModel(), $relationJoinCache);
            }

            StaticCache::clear();
            return $this;
        };
    }

    /**
     * Order by a field in the defined relationship.
     */
    public function orderByPowerJoins(): Closure
    {
        return function ($sort, $direction = 'asc', $aggregation = null, $joinType = 'join') {
            if (is_array($sort)) {
                $relationships = explode('.', $sort[0]);
                $column = $sort[1];
                $latestRelationshipName = $relationships[count($relationships) - 1];
            } else {
                $relationships = explode('.', $sort);
                $column = array_pop($relationships);
                $latestRelationshipName = $relationships[count($relationships) - 1];
            }

            $this->joinRelationship(implode('.', $relationships), null, $joinType);

            $latestRelationshipModel = array_reduce($relationships, function ($model, $relationshipName) {
                return $model->$relationshipName()->getModel();
            }, $this->getModel());

            if ($aggregation) {
                $aliasName = sprintf('%s_%s_%s',
                    $latestRelationshipModel->getTable(),
                    $column,
                    $aggregation
                );

                $this->selectRaw(
                    sprintf(
                        '%s(%s.%s) as %s',
                        $aggregation,
                        $latestRelationshipModel->getTable(),
                        $column,
                        $aliasName
                    )
                )
                    ->groupBy(sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()))
                    ->orderBy(sprintf('%s', $aliasName), $direction);
            } else {
                if ($column instanceof Expression) {
                    $this->orderBy($column, $direction);
                } else {
                    $this->orderBy(
                        sprintf('%s.%s', $latestRelationshipModel->getTable(), $column),
                        $direction
                    );
                }
            }
            return $this;
        };

    }

    public function orderByLeftPowerJoins(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, null, 'leftJoin');
        };
    }

    /**
     * Order by the COUNT aggregation using joins.
     */
    public function orderByPowerJoinsCount(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'COUNT');
        };
    }

    public function orderByLeftPowerJoinsCount(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'COUNT', 'leftJoin');
        };
    }

    /**
     * Order by the SUM aggregation using joins.
     */
    public function orderByPowerJoinsSum(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'SUM');
        };
    }

    public function orderByLeftPowerJoinsSum(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'SUM', 'leftJoin');
        };
    }

    /**
     * Order by the AVG aggregation using joins.
     */
    public function orderByPowerJoinsAvg(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'AVG');
        };
    }

    public function orderByLeftPowerJoinsAvg(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'AVG', 'leftJoin');
        };
    }

    /**
     * Order by the MIN aggregation using joins.
     */
    public function orderByPowerJoinsMin(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'MIN');
        };
    }

    public function orderByLeftPowerJoinsMin(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'MIN', 'leftJoin');
        };
    }

    /**
     * Order by the MAX aggregation using joins.
     */
    public function orderByPowerJoinsMax(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'MAX');
        };
    }

    public function orderByLeftPowerJoinsMax(): Closure
    {
        return function ($sort, $direction = 'asc') {
            return $this->orderByPowerJoins($sort, $direction, 'MAX', 'leftJoin');
        };
    }

    /**
     * Same as Laravel 'has`, but using joins instead of where exists.
     */
    public function powerJoinHas(): Closure
    {
        return function ($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null): static {
            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $this->getModel()->getTable()));
            }

            if (is_null($this->getGroupBy())) {
                $this->groupBy($this->getModel()->getQualifiedKeyName());
            }

            if (is_string($relation)) {
                if (Str::contains($relation, '.')) {
                    $this->hasNestedUsingJoins($relation, $operator, $count, 'and', $callback);

                    return $this;
                }

                $relation = $this->getRelationWithoutConstraintsProxy($relation);
            }

            $relation->performJoinForEloquentPowerJoins($this, 'leftPowerJoin', $callback);
            $relation->performHavingForEloquentPowerJoins($this, $operator, $count);
            return $this;
        };
    }

    public function hasNestedUsingJoins(): Closure
    {
        return function ($relations, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null): static {
            $relations = explode('.', $relations);

            /** @var Relation */
            $latestRelation = null;

            foreach ($relations as $index => $relation) {
                if (!$latestRelation) {
                    $relation = $this->getRelationWithoutConstraintsProxy($relation);
                } else {
                    $relation = $latestRelation->getModel()->query()->getRelationWithoutConstraintsProxy($relation);
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

    public function powerJoinDoesntHave(): Closure
    {
        return function ($relation, $boolean = 'and', Closure $callback = null) {
            return $this->powerJoinHas($relation, '<', 1, $boolean, $callback);
        };

    }

    public function powerJoinWhereHas(): Closure
    {
        return function ($relation, $callback = null, $operator = '>=', $count = 1) {
            return $this->powerJoinHas($relation, $operator, $count, 'and', $callback);
        };
    }
}
