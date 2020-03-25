<?php

namespace KirschbaumDevelopment\EloquentJoins;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use KirschbaumDevelopment\EloquentJoins\Mixins\JoinRelationship;
use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use KirschbaumDevelopment\EloquentJoins\Mixins\HasManyExtraMethods;
use KirschbaumDevelopment\EloquentJoins\Mixins\QueryBuilderExtraMethods;
use KirschbaumDevelopment\EloquentJoins\Mixins\RelationshipsExtraMethods;
use KirschbaumDevelopment\EloquentJoins\Mixins\QueryRelationshipExistence;

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
