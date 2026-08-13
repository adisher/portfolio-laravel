<?php

namespace App\Services\Social;

/**
 * Outcome of a single driver publish call. Drivers never write to the database;
 * they return one of these and the SocialPublisher records it.
 */
class PublishResult
{
    public function __construct(
        public bool $success,
        public ?string $externalId = null,
        public ?string $externalUrl = null,
        public ?string $error = null,
    ) {}

    public static function ok(?string $externalId = null, ?string $externalUrl = null): self
    {
        return new self(true, $externalId, $externalUrl, null);
    }

    public static function fail(string $error): self
    {
        return new self(false, null, null, $error);
    }
}
