<?php


namespace Illuminate\Database\Eloquent {
    use Closure;

    class Builder
    {
        // join relationship methods
        /** @return self */
        public function joinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function leftJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function rightJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function joinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function leftJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function rightJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return self */
        public function joinNestedRelationship(string $relationships, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {}

        /** @return self */
        public function orderByPowerJoins(string|array $sort, string $direction = 'asc', ?string $aggregation = null, $joinType = 'join', $aliases = null) {}

        /** @return self */
        public function orderByLeftPowerJoins(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByPowerJoinsCount(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByLeftPowerJoinsCount(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByPowerJoinsSum(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByLeftPowerJoinsSum(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByPowerJoinsAvg(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByLeftPowerJoinsAvg(string|array $sort, $direction = 'asc') {}

        /** @return self */
        public function orderByPowerJoinsMin(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByLeftPowerJoinsMin(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByPowerJoinsMax(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function orderByLeftPowerJoinsMax(string|array $sort, string $direction = 'asc') {}

        /** @return self */
        public function powerJoinHas(string $relation, string $operator = '>=', int $count = 1, string $boolean = 'and', Closure|array|string $callback = null, ?string $morphable = null) {}

        /** @return self */
        public function powerJoinDoesntHave(string $relation, string $boolean = 'and', Closure|array|string $callback = null) {}

        /** @return self */
        public function powerJoinWhereHas(string $relation, Closure|array|string $callback = null, string $operator = '>=', int $count = 1) {}

        // PowerJoinClause methods for when a closure is being used as a callback
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

        /** @return self */
        public function left() {}

        /** @return self */
        public function right() {}

        /** @return self */
        public function inner() {}
    }
}
