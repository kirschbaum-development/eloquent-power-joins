<?php

namespace Kirschbaum\PowerJoins;

/**
 * @method static as(string $alias)
 */
class FakeJoinCallback extends PowerJoinClause
{
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function __call($name, $arguments)
    {
        if ($name === 'as') {
            $this->alias = $arguments[0];
        }

        return $this;
    }
}
