<?php

namespace Kirschbaum\PowerJoins;

use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Kirschbaum\PowerJoins\Mixins\JoinRelationship;
use Kirschbaum\PowerJoins\Mixins\QueryBuilderExtraMethods;
use Kirschbaum\PowerJoins\Mixins\QueryRelationshipExistence;
use Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods;

class EloquentJoins
{
    /**
     * Register macros with Eloquent.
     */
    public static function registerEloquentMacros()
    {
        EloquentQueryBuilder::mixin(new JoinRelationship());
        EloquentQueryBuilder::mixin(new QueryRelationshipExistence());
        QueryBuilder::mixin(new QueryBuilderExtraMethods());

        Relation::mixin(new RelationshipsExtraMethods());
    }
}
