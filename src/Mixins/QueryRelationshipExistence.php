<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Illuminate\Database\Eloquent\Relations\Relation;

class QueryRelationshipExistence
{
    public function getGroupBy()
    {
        return function () {
            return $this->getQuery()->getGroupBy();
        };
    }

    public function getScopes()
    {
        return function () {
            return $this->scopes;
        };
    }

    public function getSelect()
    {
        return function () {
            return $this->getQuery()->getSelect();
        };
    }

    protected function getRelationWithoutConstraintsProxy()
    {
        return function ($relation) {
            return Relation::noConstraints(function () use ($relation) {
                return $this->getModel()->{$relation}();
            });
        };
    }
}
