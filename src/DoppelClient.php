<?php

declare(strict_types=1);

namespace Doppel;

/**
 * Entry point for capturing LLM calls to Doppel.
 *
 *   $doppel = new Doppel\DoppelClient(shadowModel: 'gpt-4o-mini'); // reads DOPPEL_API_KEY
 *
 *   // after your OpenAI call:
 *   $doppel->openAI()
 *       ->model('gpt-4o')->messages($messages)->answer($answer)
 *       ->promptTokens(10)->completionTokens(5)->totalTokens(15)->durationMs(123)
 *       ->capture();
 */
final class DoppelClient
{
    private readonly ImpactConfig $config;
    private readonly RunSink $sink;

    public function __construct(
        ?string $apiKey = null,
        ?string $shadowModel = null,
        ?string $serverUrl = null,
        ?bool $debug = null,
        ?RunSink $sink = null,
    ) {
        $this->config = Config::resolve($apiKey, $serverUrl, $shadowModel, $debug);
        $this->sink = $sink ?? new HttpRunSink($this->config);
    }

    public function config(): ImpactConfig
    {
        return $this->config;
    }

    /** Capture an OpenAI (or OpenAI-compatible) chat call. */
    public function openAI(): RunBuilder
    {
        return new RunBuilder(Provider::OpenAI, $this->config, $this->sink);
    }

    /** Capture an OpenRouter call (supports vendor and cost). */
    public function openRouter(): RunBuilder
    {
        return new RunBuilder(Provider::OpenRouter, $this->config, $this->sink);
    }

    /** Capture an Anthropic messages call. */
    public function anthropic(): RunBuilder
    {
        return new RunBuilder(Provider::Anthropic, $this->config, $this->sink);
    }

    /** Capture a Google Gemini call. */
    public function google(): RunBuilder
    {
        return new RunBuilder(Provider::Google, $this->config, $this->sink);
    }

    /** Flush pending deliveries (no-op — sends are synchronous). */
    public function flush(): void
    {
        $this->sink->flush();
    }
}
