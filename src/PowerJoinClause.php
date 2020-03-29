<?php

namespace KirschbaumDevelopment\EloquentJoins;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class PowerJoinClause extends JoinClause
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Create a new join clause instance.
     *
     * @param  \Illuminate\Database\Query\Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table, Model $model = null)
    {
        parent::__construct($parentQuery, $type, $table);

        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function __call($name, $arguments)
    {
        $scope = 'scope'.ucfirst($name);

        if (method_exists($this->getModel(), $scope)) {
            $this->getModel()->{$scope}($this, ...$arguments);
        } else {
            throw new InvalidArgumentException(sprintf('Method %s does not exist in PowerJoinClause class', $name));
        }
    }
}
