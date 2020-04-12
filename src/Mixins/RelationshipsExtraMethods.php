<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RelationshipsExtraMethods
{
    /**
     * Perform the JOIN clause for eloquent power joins.
     */
    public function performJoinForEloquentPowerJoins()
    {
        return function ($builder, $joinType = 'leftJoin', $callback = null, $alias = null) {
            if ($this instanceof BelongsToMany) {
                return $this->performJoinForEloquentPowerJoinsForBelongsToMany($builder, $joinType, $callback, $alias);
            } elseif ($this instanceof MorphOneOrMany) {
                $this->performJoinForEloquentPowerJoinsForMorph($builder, $joinType, $callback, $alias);
            } elseif ($this instanceof HasMany || $this instanceof HasOne) {
                return $this->performJoinForEloquentPowerJoinsForHasMany($builder, $joinType, $callback, $alias);
            } elseif ($this instanceof HasManyThrough) {
                return $this->performJoinForEloquentPowerJoinsForHasManyThrough($builder, $joinType, $callback, $alias);
            } else {
                return $this->performJoinForEloquentPowerJoinsForBelongsTo($builder, $joinType, $callback, $alias);
            }
        };
    }

    /**
     * Perform the JOIN clause for the BelongsTo (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsTo()
    {
        return function ($builder, $joinType, $callback = null, $alias = null) {
            $joinedTable = $this->query->getModel()->getTable();
            $parentTable = $this->getTableOrAliasForModel($this->parent, $this->parent->getTable());

            $builder->{$joinType}($joinedTable, function ($join) use ($callback, $joinedTable, $parentTable, $alias) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    $parentTable.'.'.$this->foreignKey,
                    '=',
                    $joinedTable.'.'.$this->ownerKey
                );

                if ($this->usesSoftDeletes($this->query->getModel())) {
                    $join->whereNull($joinedTable.'.'.$this->query->getModel()->getDeletedAtColumn());
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->query->getModel());
        };
    }

    /**
     * Perform the JOIN clause for the HasMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasMany()
    {
        return function ($builder, $joinType, $callback = null, $alias = null) {
            $joinedTable = $alias ?: $this->query->getModel()->getTable();
            $parentTable = $this->getTableOrAliasForModel($this->parent, $this->parent->getTable());

            $builder->{$joinType}($this->query->getModel()->getTable(), function ($join) use ($callback, $joinedTable, $parentTable, $alias) {
                if ($alias) {
                    $join->as($alias);
                }

                $join->on(
                    $this->foreignKey,
                    '=',
                    $parentTable.'.'.$this->localKey
                );

                if ($this->usesSoftDeletes($this->query->getModel())) {
                    $join->whereNull(
                        $joinedTable.'.'.$this->query->getModel()->getDeletedAtColumn()
                    );
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
        return function ($builder, $joinType, $callback = null, $alias = null) {
            [$alias1, $alias2] = $alias;

            $joinedTable = $alias1 ?: $this->getTable();
            $parentTable = $this->getTableOrAliasForModel($this->parent)[1] ?? $this->parent->getTable();

            $builder->{$joinType}($this->getTable(), function ($join) use ($callback, $joinedTable, $parentTable, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    $joinedTable.'.'.$this->getForeignPivotKeyName(),
                    '=',
                    $parentTable.'.'.$this->parentKey
                );

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback, $joinedTable, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()),
                    '=',
                    $joinedTable.'.'.$this->getRelatedPivotKeyName()
                );

                if ($this->usesSoftDeletes($this->query->getModel())) {
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
        return function ($builder, $joinType, $callback = null) {
            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback) {
                $join->on(
                    sprintf('%s.%s', $this->getModel()->getTable(), $this->getForeignKeyName()),
                    '=',
                    $this->parent->getTable().'.'.$this->localKey
                )->where($this->getMorphType(), '=', get_class($this->getModel()));

                if ($this->usesSoftDeletes($this->query->getModel())) {
                    $join->whereNull($this->query->getModel()->getQualifiedDeletedAtColumn());
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            }, $this->getModel());

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the HasManyThrough relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasManyThrough()
    {
        return function ($builder, $joinType, $callback = null, $alias = null) {
            [$alias1, $alias2] = $alias;
            $throughTable = $alias1 ?: $this->getThroughParent()->getTable();
            $farTable = $alias2 ?: $this->getModel()->getTable();

            $builder->{$joinType}($this->getThroughParent()->getTable(), function ($join) use ($callback, $throughTable, $alias1) {
                if ($alias1) {
                    $join->as($alias1);
                }

                $join->on(
                    $throughTable.'.'.$this->getFirstKeyName(),
                    '=',
                    $this->getQualifiedLocalKeyName()
                );

                if ($this->usesSoftDeletes($this->getThroughParent())) {
                    $join->whereNull($this->getThroughParent()->getQualifiedDeletedAtColumn());
                }

                if (is_array($callback) && isset($callback[$this->getThroughParent()->getTable()])) {
                    $callback[$this->getThroughParent()->getTable()]($join);
                }
            }, $this->getThroughParent());

            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback, $throughTable, $farTable, $alias1, $alias2) {
                if ($alias2) {
                    $join->as($alias2);
                }

                $join->on(
                    $farTable.'.'.$this->secondKey,
                    '=',
                    $throughTable.'.'.$this->secondLocalKey
                );

                if ($this->usesSoftDeletes($this->getModel())) {
                    $join->whereNull($farTable.'.'.$this->getModel()->getDeletedAtColumn());
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
        return function ($builder, $operator, $count) {
            $builder
                ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), $this->query->getModel()->getTable()))
                ->havingRaw(sprintf('%s_count %s %d', $this->query->getModel()->getTable(), $operator, $count));
        };
    }

    /**
     * Checks if the relationship model uses soft deletes.
     */
    public function usesSoftDeletes()
    {
        return function ($model) {
            return in_array(SoftDeletes::class, class_uses_recursive($model));
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

    public function getTableOrAliasForModel()
    {
        return function ($model, $default = null) {
            return JoinRelationship::$powerJoinAliasesCache[spl_object_id($model)] ?? $default;
        };
    }
}
