<?php

namespace Kirschbaum\PowerJoins;


trait PowerJoins
{
    public function powerJoinsGetConnectionName(): string
    {
        // TODO: This is a hack to get the connection name for in-memory databases.
        if ($this->getConnection()->getConfig()['database'] === ':memory:') {
            return $this->getConnection()->getName();
        }

        return $this->getConnection()->getConfig()['database'];
    }

    public function getQualifiedTableName(): string
    {
        return sprintf(
            '%s.%s',
            $this->powerJoinsGetConnectionName(),
            $this->getTable()
        );
    }

    public function getFullyQualifiedDeletedAtColumn(): string
    {
        return sprintf(
            '%s.%s',
            $this->powerJoinsGetConnectionName(),
            $this->getQualifiedDeletedAtColumn()
        );
    }
}
