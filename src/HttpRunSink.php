<?php

declare(strict_types=1);

namespace Doppel;

/** Delivers runs over HTTP via cURL. Errors are swallowed — capture must never
 *  break the caller's request path. */
final class HttpRunSink implements RunSink
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(private readonly ImpactConfig $config)
    {
    }

    /** @param array<string,mixed> $payload */
    public function send(array $payload): void
    {
        try {
            $url = rtrim($this->config->serverUrl, '/') . '/runs';
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                return;
            }

            if ($this->config->debug) {
                error_log('[doppel-sdk] Sending run to: ' . $url);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $this->config->apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            ]);
            curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error !== '') {
                error_log('[doppel-sdk] Failed to send run (non-blocking): ' . $error);
            } elseif ($status >= 400) {
                error_log('[doppel-sdk] Server returned non-OK: ' . $status);
            } elseif ($this->config->debug) {
                error_log('[doppel-sdk] Run sent successfully: ' . $status);
            }
        } catch (\Throwable $e) {
            error_log('[doppel-sdk] Failed to send run (non-blocking): ' . $e->getMessage());
        }
    }

    public function flush(): void
    {
        // Sends are synchronous; nothing buffered.
    }
}
