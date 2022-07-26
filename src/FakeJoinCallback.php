<?php

namespace Kirschbaum\PowerJoins;

class FakeJoinCallback
{
    protected $alias = null;
    protected $joinType = null;

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getJoinType(): ?string
    {
        return $this->joinType;
    }

    public function __call($name, $arguments)
    {
        if ($name === 'as') {
            $this->alias = $arguments[0];
        }elseif ($name === 'joinType') {
            $this->joinType = $arguments[0];
        }elseif ($name === 'left') {
            $this->joinType = 'leftPowerJoin';
        }elseif ($name === 'right') {
            $this->joinType = 'rightPowerJoin';
        }elseif ($name === 'inner') {
            $this->joinType = 'powerJoin';
        }

        return $this;
    }
}
