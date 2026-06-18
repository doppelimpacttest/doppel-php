<?php

declare(strict_types=1);

namespace Doppel;

/** Receives assembled run payloads. The default implementation POSTs them to
 *  Doppel; tests can inject a capturing sink. */
interface RunSink
{
    /** @param array<string,mixed> $payload */
    public function send(array $payload): void;

    public function flush(): void;
}
