<?php

namespace Kirschbaum\PowerJoins;

class FakeJoinCallback
{
    protected $alias = null;

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
