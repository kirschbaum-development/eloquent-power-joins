<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @mixin Builder
 */
class QueryRelationshipExistence
{
    public function getGroupBy(): \Closure
    {
        return function (): ?array {
            return $this->getQuery()->getGroupBy();
        };
    }

    public function getSelect(): \Closure
    {
        return function (): ?array {
            return $this->getQuery()->getSelect();
        };
    }

    protected function getRelationWithoutConstraintsProxy(): \Closure
    {
        return function (string $relation): ?Relation {
            return Relation::noConstraints(fn () => $this->getModel()->{$relation}());
        };
    }
}
