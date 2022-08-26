<?php

declare(strict_types=1);

namespace Juhasev\LaravelSes\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static loadMissing($relations)
 * @method array toArray()
 */
interface EmailLinkContract
{
    public function sentEmail(): BelongsTo;

    public function setClicked(bool $clicked): self;

    public function incrementClickCount(): self;

    public function getId(): mixed;

    public function originalUrl(): string;
}