<?php

namespace Kirschbaum\PowerJoins;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PowerJoinClause extends JoinClause
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Table name backup in case an alias is being used.
     *
     * @var string
     */
    public $tableName;

    /**
     * Alias name.
     */
    public ?string $alias = null;

    /**
     * Joined table alias name (mostly for belongs to many aliases).
     */
    public ?string $joinedTableAlias = null;

    /**
     * Create a new join clause instance.
     */
    public function __construct(Builder $parentQuery, $type, string $table, Model $model = null)
    {
        parent::__construct($parentQuery, $type, $table);

        $this->model = $model;
        $this->tableName = $table;
    }

    /**
     * Add an alias to the table being joined.
     */
    public function as(string $alias, ?string $joinedTableAlias = null): self
    {
        $this->alias = $alias;
        $this->joinedTableAlias = $joinedTableAlias;
        $this->table = sprintf('%s as %s', $this->table, $alias);
        $this->useTableAliasInConditions();

        if ($this->model) {
            StaticCache::setTableAliasForModel($this->model, $alias);
        }

        return $this;
    }

    public function on($first, $operator = null, $second = null, $boolean = 'and'): self
    {
        parent::on($first, $operator, $second, $boolean);
        $this->useTableAliasInConditions();

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Apply the global scopes to the joined query.
     */
    public function withGlobalScopes(): self
    {
        if (! $this->model) {
            return $this;
        }

        foreach ($this->model->getGlobalScopes() as $scope) {
            if ($scope instanceof Closure) {
                $scope->call($this, $this);
                continue;
            }

            if ($scope instanceof SoftDeletingScope) {
                continue;
            }

            (new $scope())->apply($this, $this->model);
        }

        return $this;
    }

    /**
     * Apply the table alias in the existing join conditions.
     */
    protected function useTableAliasInConditions(): self
    {
        if (! $this->alias || ! $this->model) {
            return $this;
        }

        $this->wheres = collect($this->wheres)->filter(function ($where) {
            return in_array($where['type'] ?? '', ['Column', 'Basic']);
        })->map(function ($where) {
            $key = $this->model->getKeyName();
            $table = $this->tableName;
            $replaceMethod = sprintf('useAliasInWhere%sType', ucfirst($where['type']));

            return $this->{$replaceMethod}($where);
        })->toArray();

        return $this;
    }

    protected function useAliasInWhereColumnType(array $where): array
    {
        $key = $this->model->getKeyName();
        $table = $this->tableName;

        // if it was already replaced, skip
        if (Str::startsWith($where['first'] . '.', $this->alias . '.') || Str::startsWith($where['second'] . '.', $this->alias . '.')) {
            return $where;
        }

        if (Str::contains($where['first'], $table) && Str::contains($where['second'], $table)) {
            // if joining the same table, only replace the correct table.key pair
            $where['first'] = str_replace($table . '.' . $key, $this->alias . '.' . $key, $where['first']);
            $where['second'] = str_replace($table . '.' . $key, $this->alias . '.' . $key, $where['second']);
        } else {
            $where['first'] = str_replace($table . '.', $this->alias . '.', $where['first']);
            $where['second'] = str_replace($table . '.', $this->alias . '.', $where['second']);
        }

        return $where;
    }

    protected function useAliasInWhereBasicType(array $where): array
    {
        $table = $this->tableName;

        if (Str::startsWith($where['column'] . '.', $this->alias . '.')) {
            return $where;
        }

        if (Str::contains($where['column'], $table)) {
            // if joining the same table, only replace the correct table.key pair
            $where['column'] = str_replace($table . '.', $this->alias . '.', $where['column']);
        } else {
            $where['column'] = str_replace($table . '.', $this->alias . '.', $where['column']);
        }

        return $where;
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        if ($this->alias && Str::contains($columns, $this->tableName)) {
            $columns = str_replace("{$this->tableName}.", "{$this->alias}.", $columns);
        }

        return parent::whereNull($columns, $boolean, $not);
    }

    public function newQuery(): self
    {
        return new static($this->newParentQuery(), $this->type, $this->table, $this->model); // <-- The model param is needed
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and'): self
    {
        if ($this->alias && is_string($column) && Str::contains($column, $this->tableName)) {
            $column = str_replace("{$this->tableName}.", "{$this->alias}.", $column);
        } elseif ($this->alias && ! is_callable($column)) {
            $column = $this->alias . '.' . $column;
        }

        if (is_callable($column)) {
            $query = new self($this, $this->type, $this->table, $this->model);
            $column($query);
            return $this->addNestedWhereQuery($query);
        } else {
            return parent::where($column, $operator, $value, $boolean);
        }
    }

    /**
     * Remove the soft delete condition in case the model implements soft deletes.
     */
    public function withTrashed(): self
    {
        if (! $this->getModel() || ! in_array(SoftDeletes::class, class_uses_recursive($this->getModel()))) {
            return $this;
        }

        $this->wheres = array_filter($this->wheres, function ($where) {
            if ($where['type'] === 'Null' && Str::contains($where['column'], $this->getModel()->getDeletedAtColumn())) {
                return false;
            }

            return true;
        });

        return $this;
    }

    /**
     * Remove the soft delete condition in case the model implements soft deletes.
     */
    public function onlyTrashed(): self
    {
        if (! $this->getModel()
            || ! in_array(SoftDeletes::class, class_uses_recursive($this->getModel()))
        ) {
            return $this;
        }

        $hasCondition = null;

        $this->wheres = array_map(function ($where) use (&$hasCondition) {
            if ($where['type'] === 'Null' && Str::contains($where['column'], $this->getModel()->getDeletedAtColumn())) {
                $where['type'] = 'NotNull';
                $hasCondition = true;
            }

            return $where;
        }, $this->wheres);

        if (! $hasCondition) {
            $this->whereNotNull($this->getModel()->getQualifiedDeletedAtColumn());
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        $scope = 'scope' . ucfirst($name);

        if (! $this->getModel()) {
            return;
        }

        if (method_exists($this->getModel(), $scope)) {
            return $this->getModel()->{$scope}($this, ...$arguments);
        } else {
            if (static::hasMacro($name)) {
                return $this->macroCall($name, $arguments);
            }

            $eloquentBuilder = $this->getModel()->newEloquentBuilder($this);
            if (method_exists($eloquentBuilder, $name)) {
                $eloquentBuilder->setModel($this->getModel());
                return $eloquentBuilder->{$name}(...$arguments);
            }

            throw new InvalidArgumentException(sprintf('Method %s does not exist in PowerJoinClause class', $name));
        }
    }
}
