<?php


namespace Illuminate\Database\Eloquent {
    use Closure;

    class Builder
    {
        // join relationship methods
        public function joinRelation(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function leftJoinRelation(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function rightJoinRelation(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function joinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function leftJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function rightJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}
        public function joinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}
        public function leftJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}
        public function rightJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}
        public function joinNestedRelationship(string $relationships, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {}
        public function orderByPowerJoins(string|array $sort, string $direction = 'asc', ?string $aggregation = null, $joinType = 'join', $aliases = null) {}
        public function orderByLeftPowerJoins(string|array $sort, string $direction = 'asc') {}
        public function orderByPowerJoinsCount(string|array $sort, string $direction = 'asc') {}
        public function orderByLeftPowerJoinsCount(string|array $sort, string $direction = 'asc') {}
        public function orderByPowerJoinsSum(string|array $sort, string $direction = 'asc') {}
        public function orderByLeftPowerJoinsSum(string|array $sort, string $direction = 'asc') {}
        public function orderByPowerJoinsAvg(string|array $sort, string $direction = 'asc') {}
        public function orderByLeftPowerJoinsAvg(string|array $sort, $direction = 'asc') {}
        public function orderByPowerJoinsMin(string|array $sort, string $direction = 'asc') {}
        public function orderByLeftPowerJoinsMin(string|array $sort, string $direction = 'asc') {}
        public function orderByPowerJoinsMax(string|array $sort, string $direction = 'asc') {}
        public function orderByLeftPowerJoinsMax(string|array $sort, string $direction = 'asc') {}
        public function powerJoinHas(string $relation, string $operator = '>=', int $count = 1, string $boolean = 'and', Closure|array|string $callback = null, ?string $morphable = null) {}
        public function powerJoinDoesntHave(string $relation, string $boolean = 'and', Closure|array|string $callback = null) {}
        public function powerJoinWhereHas(string $relation, Closure|array|string $callback = null, string $operator = '>=', int $count = 1) {}

        // PowerJOinClause methods for when a closure is being used as a callback
        /** @return self */
        public function as(string $alias, ?string $joinedTableAlias = null) {}

        /** @return self */
        public function on($first, $operator = null, $second = null, $boolean = 'and') {}

        /** @return self */
        public function withGlobalScopes() {}

        /** @return self */
        public function withTrashed() {}

        /** @return self */
        public function onlyTrashed() {}

        /** Left join the relation */
        public function left() {}

        /** Right join the relation */
        public function right() {}

        /** Inner join the relation */
        public function inner() {}
    }
}
