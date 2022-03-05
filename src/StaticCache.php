<?php

namespace Kirschbaum\PowerJoins;

class StaticCache
{
    /**
     * Cache to not join the same relationship twice.
     *
     * @var array
     */
    public static $powerJoinAliasesCache = [];
}
