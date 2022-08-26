<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static loadMissing($relations)
 * @method array toArray()
 */
interface BatchContract
{
    public function sentEmails(): HasMany;

    public static function resolve(string $name): BatchContract|null;

    public function getId(): mixed;
}