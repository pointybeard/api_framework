<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use pointybeard\Helpers\Functions\Flags;
use Symfony\Component\HttpFoundation;

class Route implements Interfaces\RouteInterface {

    protected const DEFAULT_VALIDATOR = "[^/]+";

    protected $url = null;
    protected $controller = null;
    protected $methods = null;
    protected $validate = [];

    protected $page = null;

    protected static $methodStringToFlagMapping = [
        HttpFoundation\Request::METHOD_GET => self::METHOD_GET,
        HttpFoundation\Request::METHOD_POST => self::METHOD_POST,
        HttpFoundation\Request::METHOD_PUT => self::METHOD_PUT,
        HttpFoundation\Request::METHOD_PATCH => self::METHOD_PATCH,
        HttpFoundation\Request::METHOD_DELETE => self::METHOD_DELETE,
        HttpFoundation\Request::METHOD_OPTIONS => self::METHOD_OPTIONS,
        HttpFoundation\Request::METHOD_HEAD => self::METHOD_HEAD,
    ];

    public function __call(string $name, array $args)
    {
        if (true == empty($args)) {
            return $this->$name;
        }

        $this->$name = $args[0];

        return $this;
    }

    public function __get($name)
    {
        return $this->$name ?? null;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function doesRespondToMethod(string $method): bool
    {
        return (bool)Flags\is_flag_set($this->methods(), self::$methodStringToFlagMapping[$method]);
    }

    private function tokenizeUrl(?int $flags = null): array
    {
        $url = false == Flags\is_flag_set($flags, self::TOKENIZER_USE_SYMPHONY_COMPATIBLE_URL)
            ? $this->url
            : $this->buildSymphonyCompatiblePageUrl()
        ;

        return explode("/", trim($url, "/"));
    }

    private function buildSymphonyCompatiblePageUrl(): string
    {
        $result = [
            "path" => [],
            "params" => []
        ];

        foreach($this->tokenizeUrl() as $part) {
            if(true == $this->isStringToken($part)) {
                $result['params'][] = $part;
            } else {
                $result['path'][] = $part;
            }
        }

        return "/" . rtrim(implode("/", $result["path"]) . "/" . implode("/", $result["params"]), "/");
    }

    private function isStringToken(string $part): bool
    {
        return $part[0] == "{" && $part[strlen($part) - 1] == "}";
    }

    private function generateRegex(?int $flags = null): string
    {
        $regex = [];
        foreach($this->tokenizeUrl($flags) as $part) {
            $regex[] = (
                false == $this->isStringToken($part) 
                    ? "({$part})" 
                    : $this->findValidatorForToken(trim($part, "{}"), $flags)
            );
        }

        return sprintf("@^/%s\$@i", implode("/", $regex));
    }

    private function findValidatorForToken(string $token, ?int $flags = null): string
    {
        return 
            sprintf("(?<%s>%s)", 
                str_replace("-", "_", $token),
                false == Flags\is_flag_set($flags, self::SKIP_REGEX_VALIDATION) && array_key_exists($token, $this->validate())
                    ? $this->validate()[$token]
                    : self::DEFAULT_VALIDATOR
            )
        ;
    }

    public function match(HttpFoundation\Request $request, ?int $flags = null): bool {
        if(false == Flags\is_flag_set($flags, self::SKIP_METHOD_MATCH) && false == $this->doesRespondToMethod($request->getMethod())) {
            return false;
        }
        return (bool)preg_match($this->generateRegex($flags), rtrim($request->getPathInfo(), '/'));
    }

    public function parse(HttpFoundation\Request $request, ?int $flags = null): ?\stdClass {

        if(false == preg_match_all($this->generateRegex($flags), rtrim($request->getPathInfo(), '/'), $matches)) {
            return null;
        }

        $parsed = (object)[
            "url" => $url,
            "elements" => []
        ];

        // The first item is always the original input string. We don't need that.
        unset($matches[0]);

        foreach($matches as $index => $match) {
            // It is expected that all matches are named, so we will ignore anything
            // that has an index that's an integer
            if(true == is_int($index)) {
                continue;
            }

            $parsed->elements[$index] = $match[0];
        }

        return $parsed;
    }

    // Using $this->url, this method will attempt to find the corresponding
    // Page entry in Symphony
    public function page(): ?\stdClass
    {
        // Only do this once
        if(false == ($this->page instanceof \stdClass)) {
            $this->page = (new PageResolver((string)$this->buildSymphonyCompatiblePageUrl()))->resolve();
        }

        return $this->page;
    }

    public function toArray(): array
    {
        return [
            "url" => $this->url(),
            "methods" => $this->methodFlagsToString(),
            "controller" => $this->controller(),
            "validate" => $this->validate(),
        ];
    }

    public function __toString() {
        return $this->toJson();
    }

    public function toJson(): string
    {
        return (string)json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    public function methodFlagsToString(): string
    {
        $mapping = array_flip(self::$methodStringToFlagMapping);
        $result = [];
        foreach($mapping as $flag => $string) {
            if(Flags\is_flag_set($this->methods(), $flag)) {
                $result[] = $string;
            }
        }
        return implode("|", $result);
    }
}
