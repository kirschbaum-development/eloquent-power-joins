<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Str;
use Kirschbaum\PowerJoins\PowerJoinClause;
use Kirschbaum\PowerJoins\StaticCache;

/**
 * @method \Illuminate\Database\Eloquent\Model getModel()
 * @method string getTable()
 * @method string getForeignPivotKeyName()
 * @method string getRelatedPivotKeyName()
 * @method bool isOneOfMany()
 * @method \Illuminate\Database\Eloquent\Builder|void getOneOfManySubQuery()
 * @method \Illuminate\Database\Eloquent\Builder getQuery()
 * @method \Illuminate\Database\Eloquent\Model getThroughParent()
 * @method string getForeignKeyName()
 * @method string getMorphType()
 * @method string getMorphClass()
 * @method string getFirstKeyName()
 * @method string getQualifiedLocalKeyName()
 * @method string getExistenceCompareKey()
 *
 * @mixin \Illuminate\Database\Eloquent\Relations\Relation
 * @mixin \Illuminate\Database\Eloquent\Relations\HasOneOrMany
 * @mixin \Illuminate\Database\Eloquent\Relations\BelongsToMany
 *
 * @property \Illuminate\Database\Eloquent\Builder $query
 * @property Model $parent
 * @property Model $throughParent
 * @property string $foreignKey
 * @property string $parentKey
 * @property string $ownerKey
 * @property string $localKey
 * @property string $secondKey
 * @property string $secondLocalKey
 * @property Model $farParent
 */
class RelationshipsExtraMethods
{
    /**
     * Perform the JOIN clause for eloquent power joins.
     */
    public function performJoinForEloquentPowerJoins()
    {
        return function ($builder, $joinType = 'leftJoin', $callback = null, $alias = null, bool $disableExtraConditions = false, ?string $morphable = null, bool $hasCheck = false) {
            // Handle cross joins differently - they don't need relationship conditions
            if ($joinType === 'crossPowerJoin') {
                return $this->performCrossJoinForEloquentPowerJoins($builder, $callback, $alias, $disableExtraConditions);
            }

            return match (true) {
                $this instanceof MorphToMany => $this->performJoinForEloquentPowerJoinsForMorphToMany($builder, $joinType, $callback, $alias, $disableExtraConditions),
                $this instanceof BelongsToMany => $this->performJoinForEloquentPowerJoinsForBelongsToMany($builder, $joinType, $callback, $alias, $disableExtraConditions),
                $this instanceof MorphOneOrMany => $this->performJoinForEloquentPowerJoinsForMorph($builder, $joinType, $callback, $alias, $disableExtraConditions),
                $this instanceof HasMany || $this instanceof HasOne => $this->performJoinForEloquentPowerJoinsForHasMany($builder, $joinType, $callback, $alias, $disableExtraConditions, $hasCheck),
                $this instanceof HasManyThrough || $this instanceof HasOneThrough => $this->performJoinForEloquentPowerJoinsForHasManyThrough($builder, $joinType, $callback, $alias, $disableExtraConditions),
                $this instanceof MorphTo => $this->performJoinForEloquentPowerJoinsForMorphTo($builder, $joinType, $callback, $alias, $disableExtraConditions, $morphable),
                default => $this->performJoinForEloquentPowerJoinsForBelongsTo($builder, $joinType, $callback, $alias, $disableExtraConditions),
            };
        };
    }

    /**
     * Perform the CROSS JOIN clause for eloquent power joins.
     */
    protected function performCrossJoinForEloquentPowerJoins()
    {
        return function ($builder, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            return match (true) {
                $this instanceof MorphToMany => $this->performCrossJoinForEloquentPowerJoinsForMorphToMany($builder, $callback, $alias, $disableExtraConditions),
                $this instanceof BelongsToMany => $this->performCrossJoinForEloquentPowerJoinsForBelongsToMany($builder, $callback, $alias, $disableExtraConditions),
                $this instanceof HasManyThrough || $this instanceof HasOneThrough => $this->performCrossJoinForEloquentPowerJoinsForHasManyThrough($builder, $callback, $alias, $disableExtraConditions),
                default => $this->performCrossJoinForEloquentPowerJoinsForSingleTable($builder, $callback, $alias, $disableExtraConditions),
            };
        };
    }

    /**
     * Perform the CROSS JOIN clause for single table relationships.
     */
    protected function performCrossJoinForEloquentPowerJoinsForSingleTable()
    {
        return function ($builder, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            $joinedTable = $this->query->getModel()->getTable();
            $tableAlias = $alias ?: $joinedTable;

            // Create a temporary join clause to capture callback conditions
            $tempJoin = null;

            $builder->crossPowerJoin($joinedTable, function ($join) use ($callback, $alias, &$tempJoin) {
                if ($alias) {
                    $join->as($alias);
                }

                $tempJoin = $join;

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->query->getModel());

            // Add soft delete conditions to the main query WHERE clause, not as join conditions
            if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                // Check if withTrashed was called by checking if the join has any trashed-related state
                $withTrashedCalled = $tempJoin && $this->crossJoinHasWithTrashedCalled($tempJoin);

                if (!$withTrashedCalled) {
                    $builder->whereNull("{$tableAlias}.{$this->query->getModel()->getDeletedAtColumn()}");
                }
            }

            // Apply extra conditions to the main query WHERE clause
            if ($disableExtraConditions === false) {
                $this->applyCrossJoinExtraConditions($builder, $tableAlias);
            }

            // Apply callback conditions to the main query WHERE clause
            if ($tempJoin && !empty($tempJoin->wheres)) {
                $this->applyCrossJoinCallbackConditions($builder, $tempJoin, $tableAlias);
            }
        };
    }

    /**
     * Perform the CROSS JOIN clause for BelongsToMany relationships.
     */
    protected function performCrossJoinForEloquentPowerJoinsForBelongsToMany()
    {
        return function ($builder, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $joinedTable = $alias1 ?: $this->getTable();
            $relatedTableAlias = $alias2 ?: $this->getModel()->getTable();

            $builder->crossPowerJoin($this->getTable(), function ($join) use ($callback, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->crossPowerJoin($this->getModel()->getTable(), function ($join) use ($callback, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            // Add soft delete conditions to the main query WHERE clause
            if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                $builder->whereNull("{$relatedTableAlias}.{$this->query->getModel()->getDeletedAtColumn()}");
            }

            // Apply extra conditions to the main query WHERE clause
            if ($disableExtraConditions === false) {
                $this->applyCrossJoinExtraConditions($builder, $relatedTableAlias);
            }

            return $this;
        };
    }

    /**
     * Perform the CROSS JOIN clause for MorphToMany relationships.
     */
    protected function performCrossJoinForEloquentPowerJoinsForMorphToMany()
    {
        return function ($builder, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $joinedTable = $alias1 ?: $this->getTable();
            $relatedTableAlias = $alias2 ?: $this->getModel()->getTable();

            $builder->crossPowerJoin($this->getTable(), function ($join) use ($callback, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->crossPowerJoin($this->getModel()->getTable(), function ($join) use ($callback, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            // Add soft delete conditions to the main query WHERE clause
            if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                $builder->whereNull("{$relatedTableAlias}.{$this->query->getModel()->getDeletedAtColumn()}");
            }

            // Apply extra conditions to the main query WHERE clause
            if ($disableExtraConditions === false) {
                $this->applyCrossJoinExtraConditions($builder, $relatedTableAlias);
            }

            return $this;
        };
    }

    /**
     * Perform the CROSS JOIN clause for HasManyThrough relationships.
     */
    protected function performCrossJoinForEloquentPowerJoinsForHasManyThrough()
    {
        return function ($builder, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;
            $throughTable = $alias1 ?: $this->getThroughParent()->getTable();
            $farTable = $alias2 ?: $this->getModel()->getTable();

            $builder->crossPowerJoin($this->getThroughParent()->getTable(), function (PowerJoinClause $join) use ($callback, $alias1, $disableExtraConditions) {
                if ($alias1) {
                    $join->as($alias1);
                }

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->getThroughParent())) {
                    $join->whereNull($this->getThroughParent()->getQualifiedDeletedAtColumn());
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if (is_array($callback) && isset($callback[$this->getThroughParent()->getTable()])) {
                    $callback[$this->getThroughParent()->getTable()]($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->getThroughParent());

            $builder->crossPowerJoin($this->getModel()->getTable(), function (PowerJoinClause $join) use ($callback, $farTable, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                if ($this->usesSoftDeletes($this->getScopes())) {
                    $join->whereNull("{$farTable}.{$this->getModel()->getDeletedAtColumn()}");
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the BelongsTo (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsTo()
    {
        return function ($query, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            $joinedTable = $this->query->getModel()->getTable();
            $parentTable = StaticCache::getTableOrAliasForModel($this->parent);

            $query->{$joinType}($joinedTable, function ($join) use ($callback, $joinedTable, $parentTable, $alias, $disableExtraConditions) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    "{$parentTable}.{$this->foreignKey}",
                    '=',
                    "{$joinedTable}.{$this->ownerKey}"
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull("{$joinedTable}.{$this->query->getModel()->getDeletedAtColumn()}");
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->query->getModel());
        };
    }

    /**
     * Perform the JOIN clause for the BelongsToMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsToMany()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $joinedTable = $alias1 ?: $this->getTable();
            $parentTable = StaticCache::getTableOrAliasForModel($this->parent);

            $builder->{$joinType}($this->getTable(), function ($join) use ($callback, $joinedTable, $parentTable, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    "{$joinedTable}.{$this->getForeignPivotKeyName()}",
                    '=',
                    "{$parentTable}.{$this->parentKey}"
                );

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback, $joinedTable, $alias2, $disableExtraConditions) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    "{$this->getModel()->getTable()}.{$this->getRelatedKeyName()}",
                    '=',
                    "{$joinedTable}.{$this->getRelatedPivotKeyName()}"
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull($this->query->getModel()->getQualifiedDeletedAtColumn());
                }

                // applying any extra conditions to the belongs to many relationship
                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the MorphToMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForMorphToMany()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $joinedTable = $alias1 ?: $this->getTable();
            $parentTable = StaticCache::getTableOrAliasForModel($this->parent);

            $builder->{$joinType}($this->getTable(), function ($join) use ($callback, $joinedTable, $parentTable, $alias1, $disableExtraConditions) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    "{$joinedTable}.{$this->getForeignPivotKeyName()}",
                    '=',
                    "{$parentTable}.{$this->parentKey}"
                );

                // applying any extra conditions to the belongs to many relationship
                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback, $joinedTable, $alias2, $disableExtraConditions) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    "{$this->getModel()->getTable()}.{$this->getModel()->getKeyName()}",
                    '=',
                    "{$joinedTable}.{$this->getRelatedPivotKeyName()}"
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull($this->query->getModel()->getQualifiedDeletedAtColumn());
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the Morph (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForMorph()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback, $disableExtraConditions, $alias) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    "{$this->getModel()->getTable()}.{$this->getForeignKeyName()}",
                    '=',
                    "{$this->parent->getTable()}.{$this->localKey}"
                )->where("{$this->getModel()->getTable()}.{$this->getMorphType()}", '=', $this->getMorphClass());

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull($this->query->getModel()->getQualifiedDeletedAtColumn());
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for when calling the morphTo method from the morphable class.
     */
    protected function performJoinForEloquentPowerJoinsForMorphTo()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false, ?string $morphable = null) {
            /** @var Model */
            $modelInstance = new $morphable();

            $builder->{$joinType}($modelInstance->getTable(), function ($join) use ($modelInstance, $callback, $disableExtraConditions) {
                $join->on(
                    "{$this->getModel()->getTable()}.{$this->getForeignKeyName()}",
                    '=',
                    "{$modelInstance->getTable()}.{$modelInstance->getKeyName()}"
                )->where("{$this->getModel()->getTable()}.{$this->getMorphType()}", '=', $modelInstance->getMorphClass());

                if ($disableExtraConditions === false && $this->usesSoftDeletes($modelInstance->getScopes())) {
                    $join->whereNull($modelInstance->getQualifiedDeletedAtColumn());
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $modelInstance);

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the HasMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasMany()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false, bool $hasCheck = false) {
            $joinedModel = $this->query->getModel();
            $joinedTable = $alias ?: $joinedModel->getTable();
            $parentTable = StaticCache::getTableOrAliasForModel($this->parent);
            $isOneOfMany = method_exists($this, 'isOneOfMany') ? $this->isOneOfMany() : false;

            if ($isOneOfMany && !$hasCheck) {
                $column = $this->getOneOfManySubQuery()->getQuery()->columns[0];
                $fkColumn = $this->getOneOfManySubQuery()->getQuery()->columns[1];
                $localKey = $this->localKey;

                $builder->where(function ($query) use ($column, $joinType, $joinedModel, $builder, $fkColumn, $parentTable, $localKey) {
                    $query->whereIn($joinedModel->getQualifiedKeyName(), function ($query) use ($column, $joinedModel, $builder, $fkColumn, $parentTable, $localKey) {
                        $columnValue = $column->getValue($builder->getGrammar());
                        $direction = Str::contains($columnValue, 'min(') ? 'asc' : 'desc';

                        $columnName = Str::of($columnValue)->after('(')->before(')')->__toString();
                        $columnName = Str::replace(['"', "'", '`'], '', $columnName);

                        if ($builder->getConnection() instanceof MySqlConnection) {
                            $query->select('*')->from(function ($query) use ($joinedModel, $columnName, $fkColumn, $direction, $parentTable, $localKey) {
                                $query
                                    ->select($joinedModel->getQualifiedKeyName())
                                    ->from($joinedModel->getTable())
                                    ->whereColumn($fkColumn, "{$parentTable}.{$localKey}")
                                    ->orderBy($columnName, $direction)
                                    ->take(1);
                            });
                        } else {
                            $query
                                ->select($joinedModel->getQualifiedKeyName())
                                ->distinct($columnName)
                                ->from($joinedModel->getTable())
                                ->whereColumn($fkColumn, "{$parentTable}.{$localKey}")
                                ->orderBy($columnName, $direction)
                                ->take(1);
                        }
                    });

                    if ($joinType === 'leftPowerJoin') {
                        $query->orWhereRaw('1 = 1');
                    }
                });
            }

            $builder->{$joinType}($this->query->getModel()->getTable(), function ($join) use ($callback, $joinedTable, $parentTable, $alias, $disableExtraConditions) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    $this->foreignKey,
                    '=',
                    "{$parentTable}.{$this->localKey}"
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        "{$joinedTable}.{$this->query->getModel()->getDeletedAtColumn()}"
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->query->getModel());
        };
    }

    /**
     * Perform the JOIN clause for the HasManyThrough relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasManyThrough()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;
            $throughTable = $alias1 ?: $this->getThroughParent()->getTable();
            $farTable = $alias2 ?: $this->getModel()->getTable();

            $builder->{$joinType}($this->getThroughParent()->getTable(), function (PowerJoinClause $join) use ($callback, $throughTable, $alias1, $disableExtraConditions) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    "{$throughTable}.{$this->getFirstKeyName()}",
                    '=',
                    $this->getQualifiedLocalKeyName()
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->getThroughParent())) {
                    $join->whereNull($this->getThroughParent()->getQualifiedDeletedAtColumn());
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join);
                }

                if (is_array($callback) && isset($callback[$this->getThroughParent()->getTable()])) {
                    $callback[$this->getThroughParent()->getTable()]($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->getThroughParent());

            $builder->{$joinType}($this->getModel()->getTable(), function (PowerJoinClause $join) use ($callback, $throughTable, $farTable, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    "{$farTable}.{$this->secondKey}",
                    '=',
                    "{$throughTable}.{$this->secondLocalKey}"
                );

                if ($this->usesSoftDeletes($this->getScopes())) {
                    $join->whereNull("{$farTable}.{$this->getModel()->getDeletedAtColumn()}");
                }

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the "HAVING" clause for eloquent power joins.
     */
    public function performHavingForEloquentPowerJoins()
    {
        return function ($builder, $operator, $count, ?string $morphable = null) {
            if ($morphable) {
                $modelInstance = new $morphable();

                $builder
                    ->selectRaw(sprintf('count(%s) as %s_count', $modelInstance->getQualifiedKeyName(), Str::replace('.', '_', $modelInstance->getTable())))
                    ->havingRaw(sprintf('count(%s) %s %d', $modelInstance->getQualifiedKeyName(), $operator, $count));
            } else {
                $builder
                    ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), Str::replace('.', '_', $this->query->getModel()->getTable())))
                    ->havingRaw(sprintf('count(%s) %s %d', $this->query->getModel()->getQualifiedKeyName(), $operator, $count));
            }
        };
    }

    /**
     * Checks if the relationship model uses soft deletes.
     */
    public function usesSoftDeletes()
    {
        /*
         * @param \Illuminate\Database\Eloquent\Model|array $model
         */
        return function ($model) {
            if ($model instanceof Model) {
                return in_array(SoftDeletes::class, class_uses_recursive($model), true);
            }

            return array_key_exists(SoftDeletingScope::class, $model);
        };
    }

    /**
     * Get the throughParent for the HasManyThrough relationship.
     */
    public function getThroughParent()
    {
        return function () {
            return $this->throughParent;
        };
    }

    /**
     * Get the farParent for the HasManyThrough relationship.
     */
    public function getFarParent()
    {
        return function () {
            return $this->farParent;
        };
    }

    public function applyExtraConditions()
    {
        return function (PowerJoinClause $join) {
            foreach ($this->getQuery()->getQuery()->wheres as $condition) {
                if ($this->shouldNotApplyExtraCondition($condition)) {
                    continue;
                }

                if (!in_array($condition['type'], ['Basic', 'Null', 'NotNull', 'Nested'], true)) {
                    continue;
                }

                $method = "apply{$condition['type']}Condition";
                $this->$method($join, $condition);
            }
        };
    }

    public function applyBasicCondition()
    {
        return function ($join, $condition) {
            $join->where($condition['column'], $condition['operator'], $condition['value'], $condition['boolean']);
        };
    }

    public function applyNullCondition()
    {
        return function ($join, $condition) {
            $join->whereNull($condition['column'], $condition['boolean']);
        };
    }

    public function applyNotNullCondition()
    {
        return function ($join, $condition) {
            $join->whereNotNull($condition['column'], $condition['boolean']);
        };
    }

    public function applyNestedCondition()
    {
        return function ($join, $condition) {
            $join->where(function ($q) use ($condition) {
                foreach ($condition['query']->wheres as $condition) {
                    $method = "apply{$condition['type']}Condition";
                    $this->$method($q, $condition);
                }
            });
        };
    }

    public function shouldNotApplyExtraCondition()
    {
        return function ($condition) {
            if (isset($condition['column']) && ($condition['column'] === '' || Str::endsWith($condition['column'], '.'))) {
                return true;
            }

            if (!$key = $this->getPowerJoinExistenceCompareKey()) {
                return true;
            }

            if (isset($condition['query'])) {
                return false;
            }

            if (is_array($key)) {
                return in_array($condition['column'], $key, true);
            }

            return $condition['column'] === $key;
        };
    }

    public function applyCrossJoinExtraConditions()
    {
        return function ($builder, $tableAlias) {
            foreach ($this->getQuery()->getQuery()->wheres as $condition) {
                if ($this->shouldNotApplyExtraCondition($condition)) {
                    continue;
                }

                if (!in_array($condition['type'], ['Basic', 'Null', 'NotNull'], true)) {
                    continue;
                }

                $method = "applyCrossJoin{$condition['type']}Condition";
                $this->$method($builder, $condition, $tableAlias);
            }
        };
    }

    public function applyCrossJoinBasicCondition()
    {
        return function ($builder, $condition, $tableAlias) {
            // Replace table name with alias if needed
            $column = $condition['column'];
            if (Str::contains($column, $this->query->getModel()->getTable())) {
                $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
            }
            $builder->where($column, $condition['operator'], $condition['value'], $condition['boolean']);
        };
    }

    public function applyCrossJoinNullCondition()
    {
        return function ($builder, $condition, $tableAlias) {
            $column = $condition['column'];
            if (Str::contains($column, $this->query->getModel()->getTable())) {
                $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
            }
            $builder->whereNull($column, $condition['boolean']);
        };
    }

    public function applyCrossJoinNotNullCondition()
    {
        return function ($builder, $condition, $tableAlias) {
            $column = $condition['column'];
            if (Str::contains($column, $this->query->getModel()->getTable())) {
                $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
            }
            $builder->whereNotNull($column, $condition['boolean']);
        };
    }

    public function crossJoinHasWithTrashedCalled()
    {
        return function ($tempJoin) {
            // Check if there are any conditions that suggest withTrashed was called
            // withTrashed removes soft delete conditions, so if we don't find the expected
            // soft delete condition, it means withTrashed was likely called
            $deletedAtColumn = $this->query->getModel()->getDeletedAtColumn();

            foreach ($tempJoin->wheres as $condition) {
                if ($condition['type'] === 'Null' && Str::contains($condition['column'], $deletedAtColumn)) {
                    return false; // Found soft delete condition, withTrashed was not called
                }
            }

            // If we have conditions but no soft delete condition, withTrashed might have been called
            // But we need a more reliable way to detect this. Let's use a different approach.
            // We'll add a flag to the PowerJoinClause when withTrashed is called
            return property_exists($tempJoin, '_withTrashedCalled') && $tempJoin->_withTrashedCalled;
        };
    }

    public function applyCrossJoinCallbackConditions()
    {
        return function ($builder, $tempJoin, $tableAlias) {
            foreach ($tempJoin->wheres as $condition) {
                if (!in_array($condition['type'], ['Basic', 'Null', 'NotNull'], true)) {
                    continue;
                }

                switch ($condition['type']) {
                    case 'Basic':
                        $column = $condition['column'];
                        if (Str::contains($column, $this->query->getModel()->getTable())) {
                            $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
                        }
                        $builder->where($column, $condition['operator'], $condition['value'], $condition['boolean']);
                        break;
                    case 'Null':
                        $column = $condition['column'];
                        if (Str::contains($column, $this->query->getModel()->getTable())) {
                            $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
                        }
                        $builder->whereNull($column, $condition['boolean']);
                        break;
                    case 'NotNull':
                        $column = $condition['column'];
                        if (Str::contains($column, $this->query->getModel()->getTable())) {
                            $column = str_replace($this->query->getModel()->getTable(), $tableAlias, $column);
                        }
                        $builder->whereNotNull($column, $condition['boolean']);
                        break;
                }
            }

            // Clear the join conditions since we've moved them to the main query
            $tempJoin->wheres = [];
        };
    }

    public function getPowerJoinExistenceCompareKey()
    {
        return function () {
            if ($this instanceof MorphTo) {
                return [$this->getMorphType(), $this->getForeignKeyName()];
            }

            if ($this instanceof BelongsTo) {
                return $this->getQualifiedOwnerKeyName();
            }

            if ($this instanceof HasMany || $this instanceof HasOne) {
                return $this->getExistenceCompareKey();
            }

            if ($this instanceof HasManyThrough || $this instanceof HasOneThrough) {
                return $this->getQualifiedFirstKeyName();
            }

            if ($this instanceof BelongsToMany) {
                return $this->getExistenceCompareKey();
            }

            if ($this instanceof MorphOneOrMany) {
                return [$this->getQualifiedMorphType(), $this->getExistenceCompareKey()];
            }
        };
    }
}
