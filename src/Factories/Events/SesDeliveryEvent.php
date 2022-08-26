<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Factories\Events;

use Illuminate\Queue\SerializesModels;
use Juhasev\LaravelSes\Contracts\SentEmailContract;
use Juhasev\LaravelSes\Factories\EventInterface;

class SesDeliveryEvent implements EventInterface
{
    use SerializesModels;

    public array $data;

    public function __construct(SentEmailContract $model)
    {
        $this->data = $model->loadMissing('batch')->toArray();
    }
}