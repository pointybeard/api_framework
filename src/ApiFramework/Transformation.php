<?php declare(strict_types=1);
namespace Symphony\ApiFramework\ApiFramework;

class Transformation
{
    private $test;
    private $action;

    public function __construct(callable $test, callable $action)
    {
        $this->test = $test;
        $this->action = $action;
    }

    public function __call($name, $args)
    {
        if (!isset($this->$name) || !is_callable($this->$name)) {
            throw new \Exception(__CLASS__ . " has no callable member '{$name}'.");
        }
        return call_user_func_array($this->$name, $args);
    }
}
