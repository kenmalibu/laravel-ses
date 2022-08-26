<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static loadMissing($relations)
 * @method array toArray()
 */
interface EmailComplaintContract
{
    public function sentEmail(): BelongsTo;

    public function getId(): mixed;
}