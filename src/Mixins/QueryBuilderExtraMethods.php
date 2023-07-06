<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Illuminate\Database\Query\Builder;

/**
 * @mixin Builder
 */
class QueryBuilderExtraMethods
{
    public function getGroupBy(): \Closure
    {
        return function (): ?array {
            return $this->groups;
        };
    }

    public function getSelect(): \Closure
    {
        return function (): ?array {
            return $this->columns;
        };
    }
}
