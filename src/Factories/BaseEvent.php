<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Factories;

class BaseEvent implements EventInterface
{
    public function send()
    {
        event($this);
    }
}