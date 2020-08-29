<?php

namespace Kirschbaum\EloquentPowerJoins;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Kirschbaum\EloquentPowerJoins\Mixins\HasManyMixin;
use Kirschbaum\EloquentPowerJoins\Mixins\BelongsToMixin;
use Kirschbaum\EloquentPowerJoins\Mixins\JoinRelationship;
use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use Kirschbaum\EloquentPowerJoins\Mixins\QueryBuilderExtraMethods;
use Kirschbaum\EloquentPowerJoins\Mixins\RelationshipsExtraMethods;
use Kirschbaum\EloquentPowerJoins\Mixins\QueryRelationshipExistence;

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
