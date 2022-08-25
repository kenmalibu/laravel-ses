<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes;

use Exception;

class ModelResolver
{
    /**
     * Resolve model name from config
     *
     * @param string $name
     * @throws Exception
     */
    public static function get($name)
    {
        $class = config('laravelses.models.'.ucfirst($name));

        if (! $class) {
            throw new Exception("Model ($name) could not be resolved");
        }

        return $class;
    }
}