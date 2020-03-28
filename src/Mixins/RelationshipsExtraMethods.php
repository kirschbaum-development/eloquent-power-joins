<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class RelationshipsExtraMethods
{
    /**
     * Perform the JOIN clause for eloquent power joins.
     */
    public function performJoinForEloquentPowerJoins()
    {
        return function ($builder, $joinType = 'leftJoin', $callback = null, $previousRelation = null) {
            if ($this instanceof BelongsToMany) {
                return $this->performJoinForEloquentPowerJoinsForBelongsToMany($builder, $joinType, $callback, $previousRelation);
            } elseif ($this instanceof MorphOneOrMany) {
                $this->performJoinForEloquentPowerJoinsForMorph($builder, $joinType, $callback, $previousRelation);
            } elseif ($this instanceof HasMany || $this instanceof HasOne) {
                return $this->performJoinForEloquentPowerJoinsForHasMany($builder, $joinType, $callback, $previousRelation);
            } else {
                return $this->performJoinForEloquentPowerJoinsForBelongsTo($builder, $joinType, $callback, $previousRelation);
            }
        };
    }

    /**
     * Perform the JOIN clause for the BelongsTo (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsTo()
    {
        return function ($builder, $joinType, $callback = null, $previousRelation = null) {
            $builder->{$joinType}($this->query->getModel()->getTable(), function ($join) use ($callback) {
                $join->on(
                    $this->parent->getTable().'.'.$this->foreignKey,
                    '=',
                    $this->query->getModel()->getTable().'.'.$this->ownerKey
                );

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            });
        };
    }

    /**
     * Perform the JOIN clause for the HasMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForHasMany()
    {
        return function ($builder, $joinType, $callback = null, $previousRelation = null) {
            $builder->{$joinType}($this->query->getModel()->getTable(), function ($join) use ($callback) {
                $join->on(
                    $this->foreignKey,
                    '=',
                    $this->parent->getTable().'.'.$this->localKey
                );

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            });
        };
    }

    /**
     * Perform the JOIN clause for the BelongsToMany (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForBelongsToMany()
    {
        return function ($builder, $joinType, $callback = null, $previousRelation = null) {
            $builder->{$joinType}($this->getTable(), function ($join) use ($callback) {
                $join->on(
                    $this->getQualifiedForeignPivotKeyName(),
                    '=',
                    $this->getQualifiedParentKeyName()
                );

                if (is_array($callback) && isset($callback[$this->getTable()])) {
                    $callback[$this->getTable()]($join);
                }
            });

            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback) {
                $join->on(
                    sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()),
                    '=',
                    $this->getQualifiedRelatedPivotKeyName()
                );

                if (is_array($callback) && isset($callback[$this->getModel()->getTable()])) {
                    $callback[$this->getModel()->getTable()]($join);
                }
            });

            return $this;
        };
    }

    /**
     * Perform the JOIN clause for the Morph (or similar) relationships.
     */
    protected function performJoinForEloquentPowerJoinsForMorph()
    {
        return function ($builder, $joinType, $callback = null, $previousRelation = null) {
            $builder->{$joinType}($this->getModel()->getTable(), function ($join) use ($callback) {
                $join->on(
                    sprintf('%s.%s', $this->getModel()->getTable(), $this->getForeignKeyName()),
                    '=',
                    $this->parent->getTable().'.'.$this->localKey
                )->where($this->getMorphType(), '=', get_class($this->getModel()));

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            });

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
}
