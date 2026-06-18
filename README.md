# doppel-sdk — PHP

Official Doppel SDK for PHP. Capture your LLM calls and forward them to Doppel.

> **Note:** like the Java SDK, PHP captures each call explicitly with a small
> fluent builder right after you make it (PHP can't transparently monkey-patch a
> client). No required runtime dependencies beyond ext-curl / ext-json.

- **Package:** `doppelimpacttest/doppel-sdk`
- **PHP:** 8.1+

## Install

```bash
composer require doppelimpacttest/doppel-sdk
```

## Usage

```php
use Doppel\DoppelClient;

$doppel = new DoppelClient(shadowModel: 'gpt-4o-mini'); // reads DOPPEL_API_KEY

// ... make your OpenAI call, then capture it:
$doppel->openAI()
    ->model('gpt-4o')
    ->messages($messages)                 // array of message maps
    ->answer($answer)
    ->promptTokens(10)->completionTokens(5)->totalTokens(15)
    ->durationMs($elapsedMs)
    ->capture();
```

Other providers:

```php
$doppel->anthropic()->model('claude-...')->prompt($prompt)->answer($out)
    ->promptTokens(8)->completionTokens(4)->durationMs($ms)->capture();

$doppel->google()->model('gemini-...')->prompt($prompt)->answer($out)
    ->promptTokens(5)->completionTokens(3)->durationMs($ms)->capture();

$doppel->openRouter()->model('anthropic/claude-3.5-sonnet')->messages($messages)
    ->answer($out)->vendor('anthropic')->cost(0.00021)->totalTokens(15)->durationMs($ms)->capture();
```

Capture never throws into your request path.

## Configuration

| Setting | Constructor arg | Environment variable | Default |
|---|---|---|---|
| API key | `apiKey:` | `DOPPEL_API_KEY` | — (required) |
| Server URL | `serverUrl:` | `DOPPEL_SERVER_URL` | `https://api.doppel.in` |
| Shadow model | `shadowModel:` | — | none (chosen in the dashboard) |
| Debug logs | `debug:` | `DOPPEL_DEBUG` (`1`/`true`) | off |

## Development

```bash
composer install
vendor/bin/phpunit
```

## Publishing

This package is published via subtree-split: pushing a `php-v*` tag splits
`sdks/php` into the `doppelimpacttest/doppel-php` repo as a `vX.Y.Z` tag, which
Packagist tracks and publishes.

```bash
# bump version in the consuming repo's tag, then:
git tag php-v0.1.0
git push origin php-v0.1.0
```
