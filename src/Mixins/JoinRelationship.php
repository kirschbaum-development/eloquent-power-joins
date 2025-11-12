<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kirschbaum\PowerJoins\JoinsHelper;
use Kirschbaum\PowerJoins\PowerJoinClause;
use Kirschbaum\PowerJoins\StaticCache;

/**
 * @mixin Builder
 *
 * @method \Illuminate\Database\Eloquent\Model getModel()
 *
 * @property Builder $query
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
        return function (QueryBuilder $parentQuery, string $type, string $table, ?Model $model = null) {
            return new PowerJoinClause($parentQuery, $type, $table, $model);
        };
    }

    /**
     * Join the relationship(s).
     */
    public function joinRelationship(): Closure
    {
        return function (
            string $relationName,
            Closure|array|string|null $callback = null,
            string $joinType = 'join',
            bool $useAlias = false,
            bool $disableExtraConditions = false,
            ?string $morphable = null,
        ) {
            $joinType = JoinsHelper::$joinMethodsMap[$joinType] ?? $joinType;
            $useAlias = is_string($callback) ? false : $useAlias;
            $joinHelper = JoinsHelper::make($this->getModel());
            $callback = $joinHelper->formatJoinCallback($callback);

            JoinsHelper::ensureModelIsUniqueToQuery($this);
            JoinsHelper::clearCacheBeforeQuery($this);

            // Check if the main table has an alias (e.g., "posts as p") and set it as the main table or alias if it does.
            $fromClause = $this->getQuery()->from;
            $mainTableOrAlias = $this->getModel()->getTable();
            if ($fromClause && preg_match('/^.+\s+as\s+["\'\`]?(.+?)["\'\`]?$/i', $fromClause, $matches)) {
                // Register the alias for the main model so joins use it
                $mainTableOrAlias = $matches[1];
                StaticCache::setTableAliasForModel($this->getModel(), $mainTableOrAlias);
            }

            if (is_null($this->getSelect())) {
                $this->select(sprintf('%s.*', $mainTableOrAlias));
            }

            if (Str::contains($relationName, '.')) {
                $this->joinNestedRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions, $morphable);

                return $this;
            }

            $relationCallback = $callback;
            if ($callback && is_array($callback) && isset($callback[$relationName]) && is_array($callback[$relationName])) {
                $relationCallback = $callback[$relationName];
            }

            $relation = $this->getModel()->{$relationName}();
            $relationQuery = $relation->getQuery();
            $alias = $joinHelper->getAliasName(
                $useAlias,
                $relation,
                $relationName,
                $relationQuery->getModel()->getTable(),
                $relationCallback
            );

            if ($relation instanceof BelongsToMany && !is_array($alias)) {
                $extraAlias = $joinHelper->getAliasName(
                    $useAlias,
                    $relation,
                    $relationName,
                    $relation->getTable(),
                    $relationCallback
                );
                $alias = [$extraAlias, $alias];
            }

            $aliasString = is_array($alias) ? implode('.', $alias) : $alias;
            $useAlias = $alias ? true : $useAlias;

            $relationJoinCache = $alias
                ? "{$aliasString}.{$relationQuery->getModel()->getTable()}.{$relationName}"
                : "{$relationQuery->getModel()->getTable()}.{$relationName}";

            if ($joinHelper->relationshipAlreadyJoined($this->getModel(), $relationJoinCache)) {
                return $this;
            }

            if ($useAlias) {
                StaticCache::setTableAliasForModel($relation->getModel(), $alias);
            }

            $joinHelper->markRelationshipAsAlreadyJoined($this->getModel(), $relationJoinCache);

            $relation->performJoinForEloquentPowerJoins(
                builder: $this,
                joinType: $joinType,
                callback: $relationCallback,
                alias: $alias,
                disableExtraConditions: $disableExtraConditions,
                morphable: $morphable,
            );

            // Clear only the related model's alias from cache after join is performed
            if ($useAlias) {
                unset(StaticCache::$powerJoinAliasesCache[spl_object_id($relation->getModel())]);
            }

            return $this;
        };
    }

    /**
     * Join the relationship(s) using table aliases.
     */
    public function joinRelationshipUsingAlias(): Closure
    {
        return function (string $relationName, Closure|array|string|null $callback = null, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relationName, $callback, 'join', true, $disableExtraConditions, morphable: $morphable);
        };
    }

    /**
     * Left join the relationship(s) using table aliases.
     */
    public function leftJoinRelationshipUsingAlias(): Closure
    {
        return function (string $relationName, Closure|array|string|null $callback = null, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relationName, $callback, 'leftJoin', true, $disableExtraConditions, morphable: $morphable);
        };
    }

    /**
     * Right join the relationship(s) using table aliases.
     */
    public function rightJoinRelationshipUsingAlias(): Closure
    {
        return function (string $relationName, Closure|array|string|null $callback = null, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relationName, $callback, 'rightJoin', true, $disableExtraConditions, morphable: $morphable);
        };
    }

    public function joinRelation(): Closure
    {
        return function (
            string $relationName,
            Closure|array|string|null $callback = null,
            string $joinType = 'join',
            bool $useAlias = false,
            bool $disableExtraConditions = false,
            ?string $morphable = null,
        ) {
            return $this->joinRelationship($relationName, $callback, $joinType, $useAlias, $disableExtraConditions, morphable: $morphable);
        };
    }

    public function leftJoinRelationship(): Closure
    {
        return function (string $relationName, Closure|array|string|null $callback = null, bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relationName, $callback, 'leftJoin', $useAlias, $disableExtraConditions, morphable: $morphable);
        };
    }

    public function leftJoinRelation(): Closure
    {
        return function (string $relation, Closure|array|string|null $callback = null, bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relation, $callback, 'leftJoin', $useAlias, $disableExtraConditions, morphable: $morphable);
        };
    }

    public function rightJoinRelationship(): Closure
    {
        return function (string $relation, Closure|array|string|null $callback = null, bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions, morphable: $morphable);
        };
    }

    public function rightJoinRelation(): Closure
    {
        return function (string $relation, Closure|array|string|null $callback = null, bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {
            return $this->joinRelationship($relation, $callback, 'rightJoin', $useAlias, $disableExtraConditions, morphable: $morphable);
        };
    }

    /**
     * Join nested relationships.
     */
    public function joinNestedRelationship(): Closure
    {
        return function (
            string $relationships,
            Closure|array|string|null $callback = null,
            string $joinType = 'join',
            bool $useAlias = false,
            bool $disableExtraConditions = false,
            ?string $morphable = null,
        ) {
            $relations = explode('.', $relationships);
            $joinHelper = JoinsHelper::make($this->getModel());
            /** @var Relation */
            $latestRelation = null;

            $part = [];
            foreach ($relations as $relationName) {
                $part[] = $relationName;
                $fullRelationName = join('.', $part);

                $currentModel = $latestRelation ? $latestRelation->getModel() : $this->getModel();
                $relation = $currentModel->{$relationName}();
                $relationCallback = null;

                if ($callback && is_array($callback) && isset($callback[$relationName])) {
                    $relationCallback = $callback[$relationName];
                }

                if ($callback && is_array($callback) && isset($callback[$fullRelationName])) {
                    $relationCallback = $callback[$fullRelationName];
                }

                $alias = $joinHelper->getAliasName(
                    $useAlias,
                    $relation,
                    $relationName,
                    $relation->getQuery()->getModel()->getTable(),
                    $relationCallback
                );

                if ($alias && $relation instanceof BelongsToMany && !is_array($alias)) {
                    $extraAlias = $joinHelper->getAliasName(
                        $useAlias,
                        $relation,
                        $relationName,
                        $relation->getTable(),
                        $relationCallback
                    );

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
                    $disableExtraConditions,
                    $morphable
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
        return function (string|array $sort, string $direction = 'asc', ?string $aggregation = null, string $joinType = 'join', $aliases = null) {
            if (is_array($sort)) {
                $relationships = explode('.', $sort[0]);
                $column = $sort[1];
                $latestRelationshipName = $relationships[count($relationships) - 1];
            } else {
                $relationships = explode('.', $sort);
                $column = array_pop($relationships);
                $latestRelationshipName = $relationships[count($relationships) - 1];
            }

            $this->joinRelationship(relationName: implode('.', $relationships), callback: $aliases, joinType: $joinType);

            $latestRelationshipModel = array_reduce($relationships, function ($model, $relationshipName) {
                return $model->$relationshipName()->getModel();
            }, $this->getModel());

            $table = $latestRelationshipModel->getTable();

            if ($aliases) {
                if (is_string($aliases)) {
                    $table = $aliases;
                }

                if (is_array($aliases) && array_key_exists($latestRelationshipName, $aliases)) {
                    $alias = $aliases[$latestRelationshipName];

                    if (is_callable($alias)) {
                        $join = collect($this->query->joins)
                            ->whereInstanceOf(PowerJoinClause::class)
                            ->firstWhere('tableName', $table);

                        $table = $join->alias;
                    }
                }
            }

            if ($aggregation) {
                $aliasName = sprintf(
                    '%s_%s_%s',
                    $table,
                    $column,
                    $aggregation
                );

                $this->selectRaw(
                    sprintf(
                        '%s(%s.%s) as %s',
                        $aggregation,
                        $table,
                        $column,
                        $aliasName
                    )
                )
                    ->groupBy(sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()))
                    ->orderBy(DB::raw(sprintf('%s', $aliasName)), $direction);
            } else {
                if ($column instanceof Expression) {
                    $this->orderBy($column, $direction);
                } else {
                    $this->orderBy(
                        sprintf('%s.%s', $table, $column),
                        $direction
                    );
                }
            }

            return $this;
        };
    }

    public function orderByLeftPowerJoins(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, joinType: 'leftJoin');
        };
    }

    /**
     * Order by the COUNT aggregation using joins.
     */
    public function orderByPowerJoinsCount(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'COUNT');
        };
    }

    public function orderByLeftPowerJoinsCount(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'COUNT', joinType: 'leftJoin');
        };
    }

    /**
     * Order by the SUM aggregation using joins.
     */
    public function orderByPowerJoinsSum(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'SUM');
        };
    }

    public function orderByLeftPowerJoinsSum(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'SUM', joinType: 'leftJoin');
        };
    }

    /**
     * Order by the AVG aggregation using joins.
     */
    public function orderByPowerJoinsAvg(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'AVG');
        };
    }

    public function orderByLeftPowerJoinsAvg(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'AVG', joinType: 'leftJoin');
        };
    }

    /**
     * Order by the MIN aggregation using joins.
     */
    public function orderByPowerJoinsMin(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'MIN');
        };
    }

    public function orderByLeftPowerJoinsMin(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'MIN', joinType: 'leftJoin');
        };
    }

    /**
     * Order by the MAX aggregation using joins.
     */
    public function orderByPowerJoinsMax(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'MAX');
        };
    }

    public function orderByLeftPowerJoinsMax(): Closure
    {
        return function (string|array $sort, string $direction = 'asc') {
            return $this->orderByPowerJoins(sort: $sort, direction: $direction, aggregation: 'MAX', joinType: 'leftJoin');
        };
    }

    /**
     * Same as Laravel 'has`, but using joins instead of where exists.
     */
    public function powerJoinHas(): Closure
    {
        return function (string $relation, string $operator = '>=', int $count = 1, $boolean = 'and', Closure|array|string|null $callback = null, ?string $morphable = null): static {
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

            $relation->performJoinForEloquentPowerJoins($this, 'leftPowerJoin', $callback, morphable: $morphable, hasCheck: true);
            $relation->performHavingForEloquentPowerJoins($this, $operator, $count, morphable: $morphable);

            return $this;
        };
    }

    public function hasNestedUsingJoins(): Closure
    {
        return function (string $relations, string $operator = '>=', int $count = 1, string $boolean = 'and', Closure|array|string|null $callback = null): static {
            $relations = explode('.', $relations);

            /** @var Relation */
            $latestRelation = null;

            foreach ($relations as $index => $relation) {
                $relationName = $relation;

                if (!$latestRelation) {
                    $relation = $this->getRelationWithoutConstraintsProxy($relation);
                } else {
                    $relation = $latestRelation->getModel()->query()->getRelationWithoutConstraintsProxy($relation);
                }

                $relation->performJoinForEloquentPowerJoins($this, 'leftPowerJoin', is_callable($callback) ? $callback : $callback[$relationName] ?? null);

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
        return function ($relation, $boolean = 'and', ?Closure $callback = null) {
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
