<?php

namespace Kirschbaum\EloquentPowerJoins;

use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship;
use Kirschbaum\EloquentPowerJoins\Mixins\QueryBuilderExtraMethods;
use Kirschbaum\EloquentPowerJoins\Mixins\QueryRelationshipExistence;
use Kirschbaum\EloquentPowerJoins\Mixins\RelationshipsExtraMethods;

class EloquentJoins
{
    /**
     * Register macros with Eloquent.
     */
    public static function registerEloquentMacros()
    {
        EloquentQueryBuilder::mixin(new JoinRelationship);
        EloquentQueryBuilder::mixin(new QueryRelationshipExistence);
        QueryBuilder::mixin(new QueryBuilderExtraMethods);

        Relation::mixin(new RelationshipsExtraMethods);
    }
}
