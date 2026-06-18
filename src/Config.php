<?php

declare(strict_types=1);

namespace Doppel;

/** Resolves arguments together with DOPPEL_* environment variables. */
final class Config
{
    public const DEFAULT_SERVER_URL = 'https://api.doppel.in';

    public static function resolve(
        ?string $apiKey,
        ?string $serverUrl,
        ?string $shadowModel,
        ?bool $debug,
    ): ImpactConfig {
        $key = self::firstNonEmpty($apiKey, self::env('DOPPEL_API_KEY'));
        if ($key === null) {
            throw new \InvalidArgumentException(
                '[DoppelClient] Missing API key. Set the DOPPEL_API_KEY environment variable '
                . '(recommended) or pass apiKey to new DoppelClient().'
            );
        }

        $url = self::firstNonEmpty($serverUrl, self::env('DOPPEL_SERVER_URL'), self::DEFAULT_SERVER_URL);

        return new ImpactConfig(
            apiKey: $key,
            serverUrl: $url,
            shadowModel: $shadowModel,
            debug: $debug ?? self::debugFromEnv(),
        );
    }

    private static function debugFromEnv(): bool
    {
        $v = strtolower(self::env('DOPPEL_DEBUG') ?? '');
        return $v === '1' || $v === 'true';
    }

    private static function env(string $name): ?string
    {
        $v = getenv($name);
        return ($v === false || $v === '') ? null : $v;
    }

    private static function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $v) {
            if ($v !== null && $v !== '') {
                return $v;
            }
        }
        return null;
    }
}
