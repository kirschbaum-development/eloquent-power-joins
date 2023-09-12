<?php

namespace Kirschbaum\PowerJoins;

use Illuminate\Database\Eloquent\Model;

class StaticCache
{
    /**
     * Cache to not join the same relationship twice.
     * @var array<int, string>
     */
    public static array $powerJoinAliasesCache = [];

    public static function getTableOrAliasForModel(Model $model): string
    {
        if (property_exists($model, 'powerJoinsInstanceId') && isset(static::$powerJoinAliasesCache[$model->powerJoinsInstanceId])) {
            return static::$powerJoinAliasesCache[$model->powerJoinsInstanceId];
        } else {
            return $model->getTable();
        }
    }

    public static function setTableAliasForModel(Model $model, $alias): void
    {
        static::$powerJoinAliasesCache[$model->powerJoinsInstanceId] = $alias;
    }

    public static function clear(): void
    {
        static::$powerJoinAliasesCache = [];
    }
}
