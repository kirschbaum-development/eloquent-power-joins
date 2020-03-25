<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class BelongsToManyExtraMethods
{
    public function performJoinForWhereHasUsingJoins()
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
}
