<?php

namespace Kirschbaum\PowerJoins\Mixins;

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
