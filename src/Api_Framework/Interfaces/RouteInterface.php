<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use Symfony\Component\HttpFoundation;

interface RouteInterface {

    public const METHOD_GET = 0x0001;
    public const METHOD_POST = 0x0002;
    public const METHOD_PUT = 0x0004;
    public const METHOD_PATCH = 0x0008;
    public const METHOD_DELETE = 0x0010;
    public const METHOD_OPTIONS = 0x0020;
    public const METHOD_HEAD = 0x0040;

    public const METHOD_ALL = self::METHOD_GET | self::METHOD_POST | self::METHOD_PUT | self::METHOD_PATCH | self::METHOD_DELETE | self::METHOD_OPTIONS | self::METHOD_HEAD;

    // Will not use custom regex patterns
    public const SKIP_REGEX_VALIDATION = 0x0080;

    // Will ignore strict method checking
    public const SKIP_METHOD_MATCH = 0x0100;
    
    // Rearranges the tokenized URL so all params are at the end
    public const TOKENIZER_USE_SYMPHONY_COMPATIBLE_URL = 0x0200;

    public function match(HttpFoundation\Request $request, ?int $flags = null): bool;
    public function parse(HttpFoundation\Request $request, ?int $flags = null): ?\stdClass;
}
