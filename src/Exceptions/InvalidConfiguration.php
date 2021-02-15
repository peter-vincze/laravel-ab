<?php

namespace PeterVincze\AbTesting\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function noExperiment(): self
    {
        return new static('There are no experiments set.');
    }

    public static function noGoal(): self
    {
        return new static('There are no goal set.');
    }

    public static function noGitBranch(): self
    {
        return new static('The git branch in config is not available from laravel base path');
    }

    public static function experiment(): self
    {
        return new static('The experiment config options are not properly set.');
    }

    public static function goal(): self
    {
        return new static('The goal config options are not properly set.');
    }
}
