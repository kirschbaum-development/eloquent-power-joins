<?php

namespace Kirschbaum\PowerJoins\Exceptions;

use Exception;

class SingleCallbackNotAllowed extends Exception
{
    /** The error message */
    protected $message = 'Single callback is not allowed here.';
}
