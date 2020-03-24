<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class HasManyExtraMethods
{
    public function performJoinForWhereHasUsingJoins()
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

    public function performHavingForHasUsingJoins()
    {
        return function ($builder, $operator, $count) {
            $builder
                ->selectRaw(sprintf('count(%s) as %s_count', $this->query->getModel()->getQualifiedKeyName(), $this->query->getModel()->getTable()))
                ->havingRaw(sprintf('%s_count %s %d', $this->query->getModel()->getTable(), $operator, $count));
        };
    }
}
