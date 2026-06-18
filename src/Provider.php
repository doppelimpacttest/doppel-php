<?php

declare(strict_types=1);

namespace Doppel;

enum Provider
{
    case OpenAI;
    case OpenRouter;
    case Anthropic;
    case Google;
}
