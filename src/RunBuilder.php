<?php

declare(strict_types=1);

namespace Doppel;

/**
 * Fluent builder that assembles a provider-shaped run payload and captures it.
 *
 *   $doppel->openAI()
 *       ->model('gpt-4o')
 *       ->messages($messages)
 *       ->answer($answer)
 *       ->promptTokens(10)->completionTokens(5)->totalTokens(15)
 *       ->durationMs(123)
 *       ->capture();
 */
final class RunBuilder
{
    private ?string $model = null;
    private mixed $messages = null;
    private ?string $prompt = null;
    private ?string $answer = null;
    private ?string $vendor = null;
    private ?string $sessionId = null;
    private ?string $question = null;
    private ?int $promptTokens = null;
    private ?int $completionTokens = null;
    private ?int $totalTokens = null;
    private ?int $durationMs = null;
    private ?int $pdfCharsSent = null;
    private ?float $temperature = null;
    private ?float $cost = null;
    private ?string $sdkLanguage = null;

    public function __construct(
        private readonly Provider $provider,
        private readonly ImpactConfig $config,
        private readonly RunSink $sink,
    ) {
    }

    public function model(string $v): self { $this->model = $v; return $this; }
    /** @param mixed $v Array of message maps (OpenAI/OpenRouter). */
    public function messages(mixed $v): self { $this->messages = $v; return $this; }
    public function prompt(string $v): self { $this->prompt = $v; return $this; }
    public function answer(string $v): self { $this->answer = $v; return $this; }
    public function vendor(string $v): self { $this->vendor = $v; return $this; }
    public function sessionId(string $v): self { $this->sessionId = $v; return $this; }
    public function question(string $v): self { $this->question = $v; return $this; }
    public function promptTokens(int $v): self { $this->promptTokens = $v; return $this; }
    public function completionTokens(int $v): self { $this->completionTokens = $v; return $this; }
    public function totalTokens(int $v): self { $this->totalTokens = $v; return $this; }
    public function durationMs(int $v): self { $this->durationMs = $v; return $this; }
    public function pdfCharsSent(int $v): self { $this->pdfCharsSent = $v; return $this; }
    public function temperature(float $v): self { $this->temperature = $v; return $this; }
    public function cost(float $v): self { $this->cost = $v; return $this; }
    public function sdkLanguage(string $v): self { $this->sdkLanguage = $v; return $this; }

    /** Assemble the payload and deliver it (fire-and-forget). Never throws. */
    public function capture(): void
    {
        try {
            $this->sink->send($this->build());
        } catch (\Throwable $e) {
            // capture must never throw into the caller
        }
    }

    /** @return array<string,mixed> */
    public function build(): array
    {
        $pt = $this->promptTokens ?? 0;
        $ct = $this->completionTokens ?? 0;
        $tt = $this->totalTokens ?? ($pt + $ct);
        $dur = $this->durationMs ?? 0;
        $ts = gmdate('Y-m-d\TH:i:s\Z');

        $p = [
            'model' => $this->model ?? '',
            'shadowModel' => $this->config->shadowModel,
        ];

        $p += match ($this->provider) {
            Provider::OpenAI => [
                'type' => 'llm-response',
                'timestamp' => $ts,
                'sessionId' => $this->sessionId,
                'temperature' => $this->temperature,
                'messages' => $this->messages,
                'question' => $this->question,
                'pdfCharsSent' => $this->pdfCharsSent,
                'answer' => $this->answer ?? '',
                'promptTokens' => $pt,
                'completionTokens' => $ct,
                'totalTokens' => $tt,
                'durationMs' => $dur,
                'sdkLanguage' => $this->sdkLanguage,
            ],
            Provider::OpenRouter => [
                'provider' => 'openrouter',
                'timestamp' => $ts,
                'sessionId' => $this->sessionId,
                'temperature' => $this->temperature,
                'messages' => $this->messages,
                'question' => $this->question,
                'pdfCharsSent' => $this->pdfCharsSent,
                'answer' => $this->answer ?? '',
                'promptTokens' => $pt,
                'completionTokens' => $ct,
                'totalTokens' => $tt,
                'durationMs' => $dur,
                'cost' => $this->cost,
                'vendor' => $this->vendor,
                'sdkLanguage' => $this->sdkLanguage,
            ],
            Provider::Google => [
                'provider' => 'google',
                'timestamp' => $ts,
                'prompt' => $this->prompt ?? '',
                'answer' => $this->answer ?? '',
                'promptTokens' => $pt,
                'completionTokens' => $ct,
                'totalTokens' => $tt,
                'durationMs' => $dur,
                'sdkLanguage' => $this->sdkLanguage,
            ],
            Provider::Anthropic => [
                'prompt' => $this->prompt ?? '',
                'promptTokens' => $pt,
                'completionTokens' => $ct,
                'totalTokens' => $tt,
                'primaryResponse' => [
                    'output' => $this->answer ?? '',
                    'latencyMs' => $dur,
                    'tokens' => $tt,
                ],
                'sdkLanguage' => $this->sdkLanguage,
            ],
        };

        // Drop nulls so optional fields are omitted (not sent as JSON null).
        return array_filter($p, static fn ($v) => $v !== null);
    }
}
