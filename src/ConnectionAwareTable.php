<?php

declare(strict_types=1);

namespace Kirschbaum\PowerJoins;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;

/**
 * Helper for building table/column references that stay correct when the
 * related model lives on a different database connection from the base query.
 *
 * For same-connection joins this class is a pass-through returning plain
 * strings — the base grammar handles quoting and prefixing as before. For
 * cross-connection joins it returns an Expression already quoted by the base
 * grammar, embedding the *related* connection's table prefix and database name
 * so the base grammar doesn't apply its own prefix on top.
 *
 * Database name qualification (the "db.table" form) is applied automatically
 * for drivers that support it (MySQL/MariaDB). SQLite is excluded because it
 * requires ATTACH DATABASE for cross-database access and does not support the
 * simple database.table syntax.
 */
class ConnectionAwareTable
{
    /**
     * Whether the given model's connection differs from the base query's model connection.
     */
    public static function isCrossConnection(Model $owner, EloquentBuilder|QueryBuilder $baseQuery): bool
    {
        $baseModel = static::baseModel($baseQuery);

        if (!$baseModel) {
            return false;
        }

        return $owner->getConnectionName() !== $baseModel->getConnectionName();
    }

    /**
     * Build the table argument for a join() call.
     *
     * Returns a plain string for same-connection joins (grammar handles prefix)
     * or an Expression with the related connection's prefix baked in for
     * cross-connection joins.
     */
    public static function tableReference(
        Model $related,
        EloquentBuilder|QueryBuilder $baseQuery,
        ?string $alias = null,
        ?string $tableName = null,
    ): string|Expression {
        $table = $tableName ?? $related->getTable();

        if (!static::isCrossConnection($related, $baseQuery)) {
            return $alias ? "{$table} as {$alias}" : $table;
        }

        $grammar = static::grammar($baseQuery);
        $wrapped = $grammar->wrap(static::prefixed($related, $table));

        if ($dbName = static::qualifiedDatabaseName($related)) {
            $wrapped = $grammar->wrap($dbName).'.'.$wrapped;
        }

        if ($alias) {
            $wrapped .= ' as '.$grammar->wrap($alias);
        }

        return new Expression($wrapped);
    }

    /**
     * Build a "table.column" reference that stays correct across connections.
     *
     * When an alias is given it takes precedence over the table name (aliases
     * are unaffected by prefixing).
     */
    public static function columnReference(
        Model $owner,
        EloquentBuilder|QueryBuilder $baseQuery,
        string $column,
        ?string $alias = null,
        ?string $tableName = null,
    ): string|Expression {
        if (!static::isCrossConnection($owner, $baseQuery)) {
            $tableOrAlias = $alias ?: ($tableName ?? $owner->getTable());

            return "{$tableOrAlias}.{$column}";
        }

        $grammar = static::grammar($baseQuery);

        if ($alias) {
            return new Expression($grammar->wrap($alias).'.'.$grammar->wrap($column));
        }

        $wrappedTable = $grammar->wrap(static::prefixed($owner, $tableName ?? $owner->getTable()));

        if ($dbName = static::qualifiedDatabaseName($owner)) {
            $wrappedTable = $grammar->wrap($dbName).'.'.$wrappedTable;
        }

        return new Expression($wrappedTable.'.'.$grammar->wrap($column));
    }

    /**
     * Return either the registered alias or a connection-aware table reference.
     */
    public static function tableOrAliasReference(
        Model $model,
        EloquentBuilder|QueryBuilder $baseQuery,
    ): string|Expression {
        $cached = StaticCache::$powerJoinAliasesCache[spl_object_id($model)] ?? null;

        if ($cached !== null && $cached !== $model->getTable()) {
            if (!static::isCrossConnection($model, $baseQuery)) {
                return $cached;
            }

            return new Expression(static::grammar($baseQuery)->wrap($cached));
        }

        return static::tableReference($model, $baseQuery);
    }

    /**
     * Produce the column reference using either the registered alias (if any)
     * or the qualified table form.
     */
    public static function columnOrAliasReference(
        Model $model,
        EloquentBuilder|QueryBuilder $baseQuery,
        string $column,
    ): string|Expression {
        $cached = StaticCache::$powerJoinAliasesCache[spl_object_id($model)] ?? null;

        if ($cached !== null && $cached !== $model->getTable()) {
            return static::columnReference($model, $baseQuery, $column, $cached);
        }

        return static::columnReference($model, $baseQuery, $column);
    }

    public static function rewriteQualifiedColumn(
        Model $owner,
        EloquentBuilder|QueryBuilder $baseQuery,
        string $columnWithTable,
        ?string $alias = null,
    ): string|Expression {
        if (!str_contains($columnWithTable, '.')) {
            return $columnWithTable;
        }

        [$tableOrAlias, $column] = explode('.', $columnWithTable, 2);

        // Only rewrite if the string refers to the owner's table (or its alias).
        if ($alias && $tableOrAlias === $alias) {
            return static::columnReference($owner, $baseQuery, $column, $alias);
        }

        if ($tableOrAlias === $owner->getTable()) {
            return static::columnReference($owner, $baseQuery, $column, $alias);
        }

        return $columnWithTable;
    }

    /**
     * Returns the database name to use as a qualifier, or null when qualification
     * is not applicable (SQLite driver or empty name).
     */
    public static function qualifiedDatabaseName(Model $model): ?string
    {
        $connection = $model->getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            return null;
        }

        $name = (string) $connection->getDatabaseName();

        return empty($name) ? null : $name;
    }

    protected static function prefixed(Model $model, string $table): string
    {
        return (string) $model->getConnection()->getTablePrefix().$table;
    }

    protected static function baseModel(EloquentBuilder|QueryBuilder $query): ?Model
    {
        if ($query instanceof EloquentBuilder) {
            return $query->getModel();
        }

        return null;
    }

    protected static function grammar(EloquentBuilder|QueryBuilder $query)
    {
        if ($query instanceof EloquentBuilder) {
            return $query->getQuery()->getGrammar();
        }

        if ($query instanceof QueryBuilder) {
            return $query->getGrammar();
        }

        throw new InvalidArgumentException('Unsupported query type: '.get_class($query));
    }
}
