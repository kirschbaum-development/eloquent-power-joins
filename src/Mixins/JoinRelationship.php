<?php

namespace Kirschbaum\PowerJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Kirschbaum\PowerJoins\PowerJoinClause;

class JoinRelationship
{
    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function powerJoin()
    {
        return function ($table, $first, $operator = null, $second = null, $type = 'inner', $where = false) {
            $model = $operator instanceof Model ? $operator : null;
            $join = $this->newPowerJoinClause($this->query, $type, $table, $model);

            // If the first "column" of the join is really a Closure instance the developer
            // is trying to build a join with a complex "on" clause containing more than
            // one condition, so we'll add the join and call a Closure with the query.
            if ($first instanceof Closure) {
                $first($join);

                $this->query->joins[] = $join;

                $this->query->addBinding($join->getBindings(), 'join');
            }

            // If the column is simply a string, we can assume the join simply has a basic
            // "on" clause with a single condition. So we will just build the join with
            // this simple join clauses attached to it. There is not a join callback.
            else {
                $method = $where ? 'where' : 'on';

                $this->query->joins[] = $join->$method($first, $operator, $second);

                $this->query->addBinding($join->getBindings(), 'join');
            }

            return $this;
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function leftPowerJoin()
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'left');
        };
    }

    /**
     * New clause for making joins, where we pass the model to the joiner class.
     */
    public function rightPowerJoin()
    {
        return function ($table, $first, $operator = null, $second = null) {
            return $this->powerJoin($table, $first, $operator, $second, 'right');
        };
    }

    public function newPowerJoinClause()
    {
        return function (QueryBuilder $parentQuery, $type, $table, Model $model = null) {
            return new PowerJoinClause($parentQuery, $type, $table, $model);
        };
    }
}
