<?php

namespace Kirschbaum\PowerJoins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use WeakMap;

class JoinsHelper
{
    public static WeakMap $instances;

    /**
     * Cache to determine which query model belongs to which query.
     * This is used to determine if a query is a clone of another
     * query and therefore if we should refresh the model in it.
     *
     * The keys are the model objects, and the value is the spl
     * object ID of the associated Eloquent builder instance.
     */
    public static WeakMap $modelQueryDictionary;

    /**
     * An array of `beforeQuery` callbacks that
     * are registered by the library.
     */
    public static WeakMap $beforeQueryCallbacks;

    protected function __construct()
    {
        static::$instances ??= new WeakMap();
        static::$modelQueryDictionary ??= new WeakMap();
        static::$beforeQueryCallbacks ??= new WeakMap();

        $this->joinRelationshipCache = new WeakMap();
    }

    public static function make($model): static
    {
        return static::$instances[$model] ??= new self();
    }

    /**
     * Cache to not join the same relationship twice.
     */
    private WeakMap $joinRelationshipCache;

    /**
     * Join method map.
     */
    public static $joinMethodsMap = [
        'join' => 'powerJoin',
        'leftJoin' => 'leftPowerJoin',
        'rightJoin' => 'rightPowerJoin',
    ];

    /**
     * Ensure that any query model can only belong to
     * maximum one query, e.g. because of cloning.
     */
    public static function ensureModelIsUniqueToQuery($query): void
    {
        $originalModel = $query->getModel();

        $querySplObjectId = spl_object_id($query);

        if (
            isset(static::$modelQueryDictionary[$originalModel])
            && static::$modelQueryDictionary[$originalModel] !== $querySplObjectId
        ) {
            // If the model is already associated with another query, we need to clone the model.
            // This can happen if a certain query, *before having interacted with the library
            // `joinRelationship()` method*, was cloned by previous code.
            $query->setModel($model = new ($query->getModel()));

            // Link the Spl Object ID of the query to the new model...
            static::$modelQueryDictionary[$model] = $querySplObjectId;

            // If there is a `JoinsHelper` with a cache associated with the old model,
            // we will copy the cache over to the new fresh model clone added to it.
            $originalJoinsHelper = JoinsHelper::make($originalModel);
            $joinsHelper = JoinsHelper::make($model);

            foreach ($originalJoinsHelper->joinRelationshipCache[$originalModel] ?? [] as $relation => $value) {
                $joinsHelper->markRelationshipAsAlreadyJoined($model, $relation);
            }
        } else {
            static::$modelQueryDictionary[$originalModel] = $querySplObjectId;
        }

        $query->onClone(static function (Builder $query) {
            $originalModel = $query->getModel();
            $originalJoinsHelper = JoinsHelper::make($originalModel);

            // Ensure the model of the cloned query is unique to the query.
            $query->setModel($model = new $originalModel());

            // Update any `beforeQueryCallbacks` to link to the new `$this` as Eloquent Query,
            // otherwise the reference to the current Eloquent query goes wrong. These query
            // callbacks are stored on the `QueryBuilder` instance and therefore do not get
            // an instance of Eloquent Builder passed, but an instance of `QueryBuilder`.
            foreach ($query->getQuery()->beforeQueryCallbacks as $key => $beforeQueryCallback) {
                /** @var Closure $beforeQueryCallback */
                if (isset(static::$beforeQueryCallbacks[$beforeQueryCallback])) {
                    static::$beforeQueryCallbacks[$query->getQuery()->beforeQueryCallbacks[$key] = $beforeQueryCallback->bindTo($query)] = true;
                }
            }

            $joinsHelper = JoinsHelper::make($model);

            foreach ($originalJoinsHelper->joinRelationshipCache[$originalModel] ?? [] as $relation => $value) {
                $joinsHelper->markRelationshipAsAlreadyJoined($model, $relation);
            }
        });
    }

    public static function clearCacheBeforeQuery($query): void
    {
        $beforeQueryCallback = function () {
            /* @var Builder $this */
            JoinsHelper::make($this->getModel())->clear();
        };

        $query->getQuery()->beforeQuery(
            $beforeQueryCallback = $beforeQueryCallback->bindTo($query)
        );

        static::$beforeQueryCallbacks[$beforeQueryCallback] = true;
    }

    /**
     * Format the join callback.
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
     */
    public function getAliasName(bool $useAlias, Relation $relation, string $relationName, string $tableName, $callback): string|array|null
    {
        if ($callback) {
            if (is_callable($callback)) {
                $fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $tableName);
                $callback($fakeJoinCallback);

                if ($fakeJoinCallback->getAlias()) {
                    return $fakeJoinCallback->getAlias();
                }
            }

            if (is_array($callback) && $relation instanceof HasOneOrManyThrough) {
                $alias = [null, null];

                $throughParentTable = $relation->getThroughParent()->getTable();
                if (isset($callback[$throughParentTable])) {
                    $fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $throughParentTable);
                    $callback[$throughParentTable]($fakeJoinCallback);

                    if ($fakeJoinCallback->getAlias()) {
                        $alias[0] = $fakeJoinCallback->getAlias();
                    }
                }

                $farParentTable = $relation->getFarParent()->getTable();
                if (isset($callback[$farParentTable])) {
                    $fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $farParentTable);
                    $callback[$farParentTable]($fakeJoinCallback);

                    if ($fakeJoinCallback->getAlias()) {
                        $alias[1] = $fakeJoinCallback->getAlias();
                    }
                }

                return $alias;
            }

            if (is_array($callback) && isset($callback[$tableName])) {
                $fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $tableName);
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
        return isset($this->joinRelationshipCache[$model][$relation]);
    }

    /**
     * Marks the relationship as already joined.
     */
    public function markRelationshipAsAlreadyJoined($model, string $relation): void
    {
        $this->joinRelationshipCache[$model] ??= [];

        $this->joinRelationshipCache[$model][$relation] = true;
    }

    public function clear(): void
    {
        $this->joinRelationshipCache = new WeakMap();
    }
}
