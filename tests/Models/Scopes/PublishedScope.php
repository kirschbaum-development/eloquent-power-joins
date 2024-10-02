<?php

namespace Kirschbaum\PowerJoins\Tests\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return void
     */
    public function apply($builder, Model $model)
    {
        $builder->where('posts.published', true);
    }
}
