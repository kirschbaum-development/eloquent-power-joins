<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class RelationshipsExtraMethods
{
    public function performJoinForWhereHasUsingJoins()
    {
        return function ($builder, $previousRelation = null) {
            if ($this instanceof BelongsToMany) {
                return $this->performJoinForWhereHasUsingJoinsForBelongsToMany($builder, $previousRelation);
            } else {
                return $this->performJoinForWhereHasUsingJoinsForHasMany($builder, $previousRelation);
            }
        };
    }

    public function performJoinForWhereHasUsingJoinsForHasMany()
    {
        return function ($builder, $previousRelation = null) {
            $builder->leftJoin(
                $this->query->getModel()->getTable(),
                $this->foreignKey,
                '=',
                $this->parent->getTable().'.'.$this->localKey
            );
        };
    }

    public function performJoinForWhereHasUsingJoinsForBelongsToMany()
    {
        return function ($builder, $previousRelation = null) {
            $builder->leftJoin(
                $this->getTable(),
                $this->getQualifiedForeignPivotKeyName(),
                '=',
                $this->getQualifiedParentKeyName()
            );

            $builder->leftJoin(
                $this->getModel()->getTable(),
                sprintf('%s.%s', $this->getModel()->getTable(), $this->getModel()->getKeyName()),
                '=',
                $this->getQualifiedRelatedPivotKeyName()
            );

            return $this;
        };
    }

    public function performHavingForHasUsingJoins()
    {
        return function ($builder, $operator, $count) {
            $builder
                ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), $this->query->getModel()->getTable()))
                ->havingRaw(sprintf('%s_count %s %d', $this->query->getModel()->getTable(), $operator, $count));
        };
    }
}
