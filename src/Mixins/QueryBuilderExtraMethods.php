<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class QueryBuilderExtraMethods
{
    public function getGroupBy()
    {
        return function () {
            return $this->groups;
        };
    }

    public function getSelect()
    {
        return function () {
            return $this->columns;
        };
    }
}
