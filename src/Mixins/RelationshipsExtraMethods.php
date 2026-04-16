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
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Kirschbaum\PowerJoins\ConnectionAwareTable;
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
     * Perform the JOIN clause for the BelongsTo (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsTo()
    {
        return function ($query, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            $relatedModel = $this->query->getModel();
            $joinTable = ConnectionAwareTable::tableReference($relatedModel, $query);
            $parentColumn = ConnectionAwareTable::columnOrAliasReference($this->parent, $query, $this->foreignKey);

            $query->{$joinType}($joinTable, function ($join) use ($callback, $relatedModel, $parentColumn, $query, $alias, $disableExtraConditions) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    $parentColumn,
                    '=',
                    ConnectionAwareTable::columnReference($relatedModel, $query, $this->ownerKey, $alias),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($relatedModel, $query, $relatedModel->getDeletedAtColumn(), $alias)
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $query, $relatedModel, $alias);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $relatedModel);
        };
    }

    /**
     * Perform the JOIN clause for the BelongsToMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsToMany()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $related = $this->getModel();
            $pivotTable = $this->getTable();

            $pivotTableArg = ConnectionAwareTable::tableReference($this->parent, $builder, tableName: $pivotTable);
            $parentColumn = ConnectionAwareTable::columnOrAliasReference($this->parent, $builder, $this->parentKey);

            $builder->{$joinType}($pivotTableArg, function ($join) use ($callback, $pivotTable, $parentColumn, $builder, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($this->parent, $builder, $this->getForeignPivotKeyName(), $alias1, $pivotTable),
                    '=',
                    $parentColumn,
                );

                if (is_array($callback) && isset($callback[$pivotTable])) {
                    $callback[$pivotTable]($join);
                }
            });

            $relatedTableArg = ConnectionAwareTable::tableReference($related, $builder);

            $builder->{$joinType}($relatedTableArg, function ($join) use ($callback, $related, $pivotTable, $builder, $alias1, $alias2, $disableExtraConditions) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($related, $builder, $this->getRelatedKeyName(), $alias2),
                    '=',
                    ConnectionAwareTable::columnReference($this->parent, $builder, $this->getRelatedPivotKeyName(), $alias1, $pivotTable),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($related, $builder, $related->getDeletedAtColumn(), $alias2)
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $related, $alias2);
                }

                if (is_array($callback) && isset($callback[$related->getTable()])) {
                    $callback[$related->getTable()]($join);
                }
            }, $related);

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

            $related = $this->getModel();
            $pivotTable = $this->getTable();

            $pivotTableArg = ConnectionAwareTable::tableReference($this->parent, $builder, tableName: $pivotTable);
            $parentColumn = ConnectionAwareTable::columnOrAliasReference($this->parent, $builder, $this->parentKey);

            $builder->{$joinType}($pivotTableArg, function ($join) use ($callback, $pivotTable, $parentColumn, $builder, $alias1, $disableExtraConditions) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($this->parent, $builder, $this->getForeignPivotKeyName(), $alias1, $pivotTable),
                    '=',
                    $parentColumn,
                );

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $this->parent, $alias1, $pivotTable);
                }

                if (is_array($callback) && isset($callback[$pivotTable])) {
                    $callback[$pivotTable]($join);
                }
            });

            $relatedTableArg = ConnectionAwareTable::tableReference($related, $builder);

            $builder->{$joinType}($relatedTableArg, function ($join) use ($callback, $related, $pivotTable, $builder, $alias1, $alias2, $disableExtraConditions) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($related, $builder, $related->getKeyName(), $alias2),
                    '=',
                    ConnectionAwareTable::columnReference($this->parent, $builder, $this->getRelatedPivotKeyName(), $alias1, $pivotTable),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($related, $builder, $related->getDeletedAtColumn(), $alias2)
                    );
                }

                if (is_array($callback) && isset($callback[$related->getTable()])) {
                    $callback[$related->getTable()]($join);
                }
            }, $related);

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the Morph (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForMorph()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            $related = $this->getModel();
            $joinTable = ConnectionAwareTable::tableReference($related, $builder);

            $builder->{$joinType}($joinTable, function ($join) use ($callback, $related, $builder, $disableExtraConditions, $alias) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($related, $builder, $this->getForeignKeyName(), $alias),
                    '=',
                    ConnectionAwareTable::columnOrAliasReference($this->parent, $builder, $this->localKey),
                )->where(
                    ConnectionAwareTable::columnReference($related, $builder, $this->getMorphType(), $alias),
                    '=',
                    $this->getMorphClass(),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($related, $builder, $related->getDeletedAtColumn(), $alias)
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $related, $alias);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $related);

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
            $related = $this->getModel();

            $joinTable = ConnectionAwareTable::tableReference($modelInstance, $builder);

            $builder->{$joinType}($joinTable, function ($join) use ($modelInstance, $related, $builder, $callback, $disableExtraConditions) {
                $join->on(
                    ConnectionAwareTable::columnReference($related, $builder, $this->getForeignKeyName()),
                    '=',
                    ConnectionAwareTable::columnReference($modelInstance, $builder, $modelInstance->getKeyName()),
                )->where(
                    ConnectionAwareTable::columnReference($related, $builder, $this->getMorphType()),
                    '=',
                    $modelInstance->getMorphClass(),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($modelInstance->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($modelInstance, $builder, $modelInstance->getDeletedAtColumn())
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $modelInstance);
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
            $parentTableOrAlias = StaticCache::getTableOrAliasForModel($this->parent);
            $isOneOfMany = method_exists($this, 'isOneOfMany') ? $this->isOneOfMany() : false;

            if ($isOneOfMany && !$hasCheck) {
                $column = $this->getOneOfManySubQuery()->getQuery()->columns[0];
                $fkColumn = $this->getOneOfManySubQuery()->getQuery()->columns[1];
                $localKey = $this->localKey;

                $builder->where(function ($query) use ($column, $joinType, $joinedModel, $builder, $fkColumn, $parentTableOrAlias, $localKey) {
                    $query->whereIn($joinedModel->getQualifiedKeyName(), function ($query) use ($column, $joinedModel, $builder, $fkColumn, $parentTableOrAlias, $localKey) {
                        $columnValue = $column->getValue($builder->getGrammar());
                        $direction = Str::contains($columnValue, 'min(') ? 'asc' : 'desc';

                        $columnName = Str::of($columnValue)->after('(')->before(')')->__toString();
                        $columnName = Str::replace(['"', "'", '`'], '', $columnName);

                        if ($builder->getConnection() instanceof MySqlConnection) {
                            $query->select('*')->from(function ($query) use ($joinedModel, $columnName, $fkColumn, $direction, $parentTableOrAlias, $localKey) {
                                $query
                                    ->select($joinedModel->getQualifiedKeyName())
                                    ->from($joinedModel->getTable())
                                    ->whereColumn($fkColumn, "{$parentTableOrAlias}.{$localKey}")
                                    ->orderBy($columnName, $direction)
                                    ->take(1);
                            });
                        } else {
                            $query
                                ->select($joinedModel->getQualifiedKeyName())
                                ->distinct($columnName)
                                ->from($joinedModel->getTable())
                                ->whereColumn($fkColumn, "{$parentTableOrAlias}.{$localKey}")
                                ->orderBy($columnName, $direction)
                                ->take(1);
                        }
                    });

                    if ($joinType === 'leftPowerJoin') {
                        $query->orWhereRaw('1 = 1');
                    }
                });
            }

            $joinTable = ConnectionAwareTable::tableReference($joinedModel, $builder);
            $foreignKeyColumn = $this->buildHasManyForeignKeyReference($joinedModel, $builder, $alias);

            $builder->{$joinType}($joinTable, function ($join) use ($callback, $joinedModel, $foreignKeyColumn, $builder, $alias, $disableExtraConditions) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    $foreignKeyColumn,
                    '=',
                    ConnectionAwareTable::columnOrAliasReference($this->parent, $builder, $this->localKey),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($this->query->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($joinedModel, $builder, $joinedModel->getDeletedAtColumn(), $alias)
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $joinedModel, $alias);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $joinedModel);
        };
    }

    /**
     * Build the foreign key column reference for HasMany, handling the case
     * where the foreign key may already be qualified ("table.col") upstream.
     */
    protected function buildHasManyForeignKeyReference()
    {
        return function (Model $joinedModel, $builder, ?string $alias = null) {
            $foreignKey = $this->foreignKey;

            if (str_contains($foreignKey, '.')) {
                [$table, $column] = explode('.', $foreignKey, 2);

                if ($table === $joinedModel->getTable()) {
                    return ConnectionAwareTable::columnReference($joinedModel, $builder, $column, $alias);
                }

                return $foreignKey;
            }

            return ConnectionAwareTable::columnReference($joinedModel, $builder, $foreignKey, $alias);
        };
    }

    /**
     * Perform the JOIN clause for the HasManyThrough relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasManyThrough()
    {
        return function ($builder, $joinType, $callback = null, $alias = null, bool $disableExtraConditions = false) {
            [$alias1, $alias2] = $alias;

            $throughParent = $this->getThroughParent();
            $farModel = $this->getModel();

            $throughTableArg = ConnectionAwareTable::tableReference($throughParent, $builder);

            $builder->{$joinType}($throughTableArg, function (PowerJoinClause $join) use ($callback, $throughParent, $builder, $alias1, $disableExtraConditions) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($throughParent, $builder, $this->getFirstKeyName(), $alias1),
                    '=',
                    ConnectionAwareTable::columnOrAliasReference($this->getFarParent(), $builder, $this->localKey),
                );

                if ($disableExtraConditions === false && $this->usesSoftDeletes($throughParent)) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($throughParent, $builder, $throughParent->getDeletedAtColumn(), $alias1)
                    );
                }

                if ($disableExtraConditions === false) {
                    $this->applyExtraConditions($join, $builder, $throughParent, $alias1);
                }

                if (is_array($callback) && isset($callback[$throughParent->getTable()])) {
                    $callback[$throughParent->getTable()]($join);
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $throughParent);

            $farTableArg = ConnectionAwareTable::tableReference($farModel, $builder);

            $builder->{$joinType}($farTableArg, function (PowerJoinClause $join) use ($callback, $throughParent, $farModel, $builder, $alias1, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    ConnectionAwareTable::columnReference($farModel, $builder, $this->secondKey, $alias2),
                    '=',
                    ConnectionAwareTable::columnReference($throughParent, $builder, $this->secondLocalKey, $alias1),
                );

                if ($this->usesSoftDeletes($this->getScopes())) {
                    $join->whereNull(
                        ConnectionAwareTable::columnReference($farModel, $builder, $farModel->getDeletedAtColumn(), $alias2)
                    );
                }

                if (is_array($callback) && isset($callback[$farModel->getTable()])) {
                    $callback[$farModel->getTable()]($join);
                }
            }, $farModel);

            return $this;
        };
    }

    /**
     * Perform the "HAVING" clause for eloquent power joins.
     */
    public function performHavingForEloquentPowerJoins()
    {
        return function ($builder, $operator, $count, ?string $morphable = null) {
            if (is_null($builder->getSelect())) {
                $builder->select(sprintf('%s.*', $builder->getModel()->getTable()));
            }

            $target = $morphable ? new $morphable() : $this->query->getModel();

            $qualifiedKey = ConnectionAwareTable::columnReference($target, $builder, $target->getKeyName());
            $countExpression = $qualifiedKey instanceof Expression
                ? $qualifiedKey->getValue($builder->getQuery()->getGrammar())
                : $qualifiedKey;

            $countAlias = Str::replace('.', '_', $target->getTable()).'_count';

            $builder
                ->selectRaw(sprintf('count(%s) as %s', $countExpression, $countAlias))
                ->havingRaw(sprintf('count(%s) %s %d', $countExpression, $operator, $count));
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
        return function (PowerJoinClause $join, $baseQuery = null, ?Model $owner = null, ?string $alias = null, ?string $tableName = null) {
            $baseQuery ??= $join;
            $owner ??= $this->query->getModel();

            foreach ($this->getQuery()->getQuery()->wheres as $condition) {
                if ($this->shouldNotApplyExtraCondition($condition)) {
                    continue;
                }

                if (!in_array($condition['type'], ['Basic', 'Null', 'NotNull', 'Nested'], true)) {
                    continue;
                }

                $method = "apply{$condition['type']}Condition";
                $this->$method($join, $condition, $baseQuery, $owner, $alias, $tableName);
            }
        };
    }

    public function applyBasicCondition()
    {
        return function ($join, $condition, $baseQuery = null, ?Model $owner = null, ?string $alias = null, ?string $tableName = null) {
            $column = $this->rewriteExtraConditionColumn($condition['column'], $baseQuery, $owner, $alias, $tableName);
            $join->where($column, $condition['operator'], $condition['value'], $condition['boolean']);
        };
    }

    public function applyNullCondition()
    {
        return function ($join, $condition, $baseQuery = null, ?Model $owner = null, ?string $alias = null, ?string $tableName = null) {
            $column = $this->rewriteExtraConditionColumn($condition['column'], $baseQuery, $owner, $alias, $tableName);
            $join->whereNull($column, $condition['boolean']);
        };
    }

    public function applyNotNullCondition()
    {
        return function ($join, $condition, $baseQuery = null, ?Model $owner = null, ?string $alias = null, ?string $tableName = null) {
            $column = $this->rewriteExtraConditionColumn($condition['column'], $baseQuery, $owner, $alias, $tableName);
            $join->whereNotNull($column, $condition['boolean']);
        };
    }

    public function applyNestedCondition()
    {
        return function ($join, $condition, $baseQuery = null, ?Model $owner = null, ?string $alias = null, ?string $tableName = null) {
            $join->where(function ($q) use ($condition, $baseQuery, $owner, $alias, $tableName) {
                foreach ($condition['query']->wheres as $condition) {
                    $method = "apply{$condition['type']}Condition";
                    $this->$method($q, $condition, $baseQuery, $owner, $alias, $tableName);
                }
            });
        };
    }

    /**
     * Rewrite the column of an extra condition so it uses the related
     * connection's prefix when the models live on different connections.
     */
    protected function rewriteExtraConditionColumn()
    {
        return function ($column, $baseQuery, ?Model $owner, ?string $alias, ?string $tableName) {
            if (!is_string($column) || !$baseQuery || !$owner) {
                return $column;
            }

            if (!str_contains($column, '.')) {
                return $column;
            }

            [$tableOrAlias, $columnName] = explode('.', $column, 2);
            $ownerTable = $tableName ?? $owner->getTable();

            if ($tableOrAlias === $ownerTable) {
                return ConnectionAwareTable::columnReference($owner, $baseQuery, $columnName, $alias, $tableName);
            }

            if ($alias && $tableOrAlias === $alias) {
                return ConnectionAwareTable::columnReference($owner, $baseQuery, $columnName, $alias, $tableName);
            }

            return $column;
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
