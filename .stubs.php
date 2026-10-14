<?php


namespace Illuminate\Database\Eloquent {
    use Closure;

    class Builder
    {
        // join relationship methods
        /** @return static */
        public function joinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function leftJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function rightJoinRelationship(string $relationName, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function joinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function leftJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function rightJoinRelationshipUsingAlias(string $relationName, Closure|array|string $callback = null, bool $disableExtraConditions = false, string $morphable = null) {}

        /** @return static */
        public function joinNestedRelationship(string $relationships, Closure|array|string $callback = null, string $joinType = 'join', bool $useAlias = false, bool $disableExtraConditions = false, ?string $morphable = null) {}

        /** @return static */
        public function orderByPowerJoins(string|array $sort, string $direction = 'asc', ?string $aggregation = null, $joinType = 'join', $aliases = null) {}

        /** @return static */
        public function orderByLeftPowerJoins(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByPowerJoinsCount(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByLeftPowerJoinsCount(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByPowerJoinsSum(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByLeftPowerJoinsSum(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByPowerJoinsAvg(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByLeftPowerJoinsAvg(string|array $sort, $direction = 'asc') {}

        /** @return static */
        public function orderByPowerJoinsMin(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByLeftPowerJoinsMin(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByPowerJoinsMax(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function orderByLeftPowerJoinsMax(string|array $sort, string $direction = 'asc') {}

        /** @return static */
        public function powerJoinHas(string $relation, string $operator = '>=', int $count = 1, string $boolean = 'and', Closure|array|string $callback = null, ?string $morphable = null) {}

        /** @return static */
        public function powerJoinDoesntHave(string $relation, string $boolean = 'and', Closure|array|string $callback = null) {}

        /** @return static */
        public function powerJoinWhereHas(string $relation, Closure|array|string $callback = null, string $operator = '>=', int $count = 1) {}

        // PowerJoinClause methods for when a closure is being used as a callback
        /** @return static */
        public function as(string $alias, ?string $joinedTableAlias = null) {}

        /** @return static */
        public function on($first, $operator = null, $second = null, $boolean = 'and') {}

        /** @return static */
        public function withGlobalScopes() {}

        /** @return static */
        public function withTrashed() {}

        /** @return static */
        public function onlyTrashed() {}

        /** @return static */
        public function left() {}

        /** @return static */
        public function right() {}

        /** @return static */
        public function inner() {}
    }
}
