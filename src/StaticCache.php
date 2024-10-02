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
        return static::$powerJoinAliasesCache[spl_object_id($model)] ?? $model->getTable();
    }

    public static function setTableAliasForModel(Model $model, $alias): void
    {
        static::$powerJoinAliasesCache[spl_object_id($model)] = $alias;
    }

    public static function clear(): void
    {
        static::$powerJoinAliasesCache = [];
    }
}
