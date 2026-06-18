<?php

declare(strict_types=1);

namespace Doppel\Tests;

use Doppel\DoppelClient;
use Doppel\RunSink;
use PHPUnit\Framework\TestCase;

/** Captures payloads instead of POSTing them. */
final class CapturingSink implements RunSink
{
    /** @var array<int,array<string,mixed>> */
    public array $runs = [];

    public function send(array $payload): void
    {
        $this->runs[] = $payload;
    }

    public function flush(): void
    {
    }
}

final class RunBuilderTest extends TestCase
{
    private function client(CapturingSink $sink): DoppelClient
    {
        return new DoppelClient(
            apiKey: 'dp_test',
            shadowModel: 'gpt-4o-mini',
            serverUrl: 'http://localhost:4000',
            sink: $sink,
        );
    }

    // ─── Config ──────────────────────────────────────────────────────────────

    public function testMissingApiKeyThrows(): void
    {
        if (getenv('DOPPEL_API_KEY') !== false) {
            $this->markTestSkipped('DOPPEL_API_KEY is set in the environment');
        }
        $this->expectException(\InvalidArgumentException::class);
        new DoppelClient();
    }

    public function testDefaultServerUrl(): void
    {
        $client = new DoppelClient(apiKey: 'k');
        $this->assertSame('https://api.doppel.in', $client->config()->serverUrl);
    }

    // ─── OpenAI ──────────────────────────────────────────────────────────────

    public function testOpenAiPayload(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->openAI()
            ->model('gpt-4o')
            ->messages([['role' => 'user', 'content' => 'Hi']])
            ->answer('Hello!')
            ->promptTokens(10)->completionTokens(5)->totalTokens(15)
            ->durationMs(123)
            ->capture();

        $run = $sink->runs[0];
        $this->assertSame('llm-response', $run['type']);
        $this->assertSame('gpt-4o', $run['model']);
        $this->assertSame('gpt-4o-mini', $run['shadowModel']);
        $this->assertSame('Hello!', $run['answer']);
        $this->assertSame(15, $run['totalTokens']);
        $this->assertArrayNotHasKey('provider', $run);
    }

    public function testTotalTokensDefaultsToSum(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->openAI()->model('gpt-4o')->messages([])->promptTokens(7)->completionTokens(3)->capture();
        $this->assertSame(10, $sink->runs[0]['totalTokens']);
    }

    // ─── OpenRouter ──────────────────────────────────────────────────────────

    public function testOpenRouterVendorAndCost(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->openRouter()
            ->model('anthropic/claude-3.5-sonnet')->messages([])->answer('Routed')
            ->vendor('anthropic')->cost(0.00021)->totalTokens(5)->durationMs(50)
            ->capture();

        $run = $sink->runs[0];
        $this->assertSame('openrouter', $run['provider']);
        $this->assertSame('anthropic', $run['vendor']);
        $this->assertSame(0.00021, $run['cost']);
        $this->assertArrayNotHasKey('type', $run);
    }

    // ─── Anthropic ───────────────────────────────────────────────────────────

    public function testAnthropicNestsPrimaryResponse(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->anthropic()
            ->model('claude-3-5-sonnet')->prompt('Hi')->answer('Hello from Claude')
            ->promptTokens(8)->completionTokens(4)->durationMs(200)
            ->capture();

        $run = $sink->runs[0];
        $this->assertSame('Hi', $run['prompt']);
        $this->assertSame(12, $run['totalTokens']);
        $this->assertSame('Hello from Claude', $run['primaryResponse']['output']);
        $this->assertSame(200, $run['primaryResponse']['latencyMs']);
        $this->assertSame(12, $run['primaryResponse']['tokens']);
    }

    // ─── Google ──────────────────────────────────────────────────────────────

    public function testGooglePayload(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->google()
            ->model('gemini-2.5-flash')->prompt('Hello')->answer('Hi from Gemini')
            ->promptTokens(5)->completionTokens(3)->totalTokens(8)->durationMs(80)
            ->capture();

        $run = $sink->runs[0];
        $this->assertSame('google', $run['provider']);
        $this->assertSame('Hello', $run['prompt']);
        $this->assertSame(8, $run['totalTokens']);
    }

    // ─── Behaviour ───────────────────────────────────────────────────────────

    public function testCaptureSendsOnce(): void
    {
        $sink = new CapturingSink();
        $this->client($sink)->openAI()->model('gpt-4o')->messages([])->capture();
        $this->assertCount(1, $sink->runs);
    }

    public function testShadowModelOmittedWhenUnset(): void
    {
        $sink = new CapturingSink();
        (new DoppelClient(apiKey: 'k', sink: $sink))->openAI()->model('gpt-4o')->messages([])->capture();
        $this->assertArrayNotHasKey('shadowModel', $sink->runs[0]);
    }

    public function testFlushIsSafe(): void
    {
        $this->client(new CapturingSink())->flush();
        $this->assertTrue(true);
    }
}
