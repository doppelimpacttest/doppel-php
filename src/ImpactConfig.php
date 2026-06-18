<?php

declare(strict_types=1);

namespace Doppel;

/** Fully-resolved configuration consumed by the capture builders. */
final class ImpactConfig
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $serverUrl,
        public readonly ?string $shadowModel,
        public readonly bool $debug,
    ) {
    }
}
