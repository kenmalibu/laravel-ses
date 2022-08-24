<?php

namespace Juhasev\LaravelSes\Factories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EventFactory
{
    /**
     * Create processor class
     *
     * @param string $eventName
     * @param string $modelName
     * @param int $modelId
     * @return EventInterface
     */
    public static function create(string $eventName, string $modelName, int $modelId): EventInterface
    {
        $class = 'Juhasev\\LaravelSes\\Factories\\Events\\Ses' . $eventName. 'Event';

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Class '.$class.' not found in SES EventFactory!');
        }

        try {
            return new $class($modelName,$modelId);
        } catch (ModelNotFoundException $e) {
            Log::error('Failed to send event [Ses'.$eventName.'Event]. '.$e->getMessage().' '.$e->getTraceAsString());
        }
    }
}
