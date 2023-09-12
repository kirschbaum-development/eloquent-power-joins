<?php

namespace Kirschbaum\PowerJoins;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

class JoinsHelper
{
    static array $instances = [];

    protected function __construct()
    {

    }

    public static function make(): static
    {
        $objects = array_map(fn ($object) => spl_object_id($object), func_get_args());

        return static::$instances[implode('-', $objects)] ??= new self();
    }

    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    private array $joinRelationshipCache = [];

    /**
     * Join method map.
     */
    public static $joinMethodsMap = [
        'join' => 'powerJoin',
        'leftJoin' => 'leftPowerJoin',
        'rightJoin' => 'rightPowerJoin',
    ];


    /**
     * Format the join callback.
     *
     * @param  mixed  $callback
     * @return mixed
     */
    public function formatJoinCallback($callback)
    {
        if (is_string($callback)) {
            return function ($join) use ($callback) {
                $join->as($callback);
            };
        }

        return $callback;
    }

    public function generateAliasForRelationship(Relation $relation, string $relationName): array|string
    {
        if ($relation instanceof BelongsToMany || $relation instanceof HasManyThrough) {
            return [
                md5($relationName.'table1'.time()),
                md5($relationName.'table2'.time()),
            ];
        }

        return md5($relationName.time());
    }

    /**
     * Get the join alias name from all the different options.
     *
     * @return string|null
     */
    public function getAliasName(bool $useAlias, Relation $relation, string $relationName, string $tableName, $callback)
    {
        if ($callback) {
            if (is_callable($callback)) {
                $fakeJoinCallback = new FakeJoinCallback();
                $callback($fakeJoinCallback);

                if ($fakeJoinCallback->getAlias()) {
                    return $fakeJoinCallback->getAlias();
                }
            }

            if (is_array($callback) && isset($callback[$tableName])) {
                $fakeJoinCallback = new FakeJoinCallback();
                $callback[$tableName]($fakeJoinCallback);

                if ($fakeJoinCallback->getAlias()) {
                    return $fakeJoinCallback->getAlias();
                }
            }
        }

        return $useAlias
            ? $this->generateAliasForRelationship($relation, $relationName)
            : null;
    }


    /**
     * Checks if the relationship was already joined.
     */
    public function relationshipAlreadyJoined($model, string $relation): bool
    {
        return isset($this->joinRelationshipCache[spl_object_id($model)][$relation]);
    }

    /**
     * Marks the relationship as already joined.
     */
    public function markRelationshipAsAlreadyJoined($model, string $relation): void
    {
        $this->joinRelationshipCache[spl_object_id($model)][$relation] = true;
    }

    public function clear(): void
    {
        $this->joinRelationshipCache = [];
    }
}
