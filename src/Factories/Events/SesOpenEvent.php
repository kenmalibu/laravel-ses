<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Factories\Events;

use Illuminate\Queue\SerializesModels;
use Juhasev\LaravelSes\Contracts\EmailOpenContract;
use Juhasev\LaravelSes\Factories\EventInterface;

class SesOpenEvent implements EventInterface
{
    use SerializesModels;

    public array $data;

    public function __construct(EmailOpenContract $model)
    {
        $this->data = $model->loadMissing([
            'sentEmail:id,message_id,email,batch_id,sent_at,delivered_at',
            'sentEmail.batch'
        ])->toArray();
    }
}