<?php

namespace Symphony\ApiFramework\Lib;

abstract class AbstractApiException extends \Exception
{
    private $status;

    public function getHttpStatusCode() {
        return $this->status;
    }

    public function __construct($status, $message, $code = 0, Exception $previous = null) {
        // Save the status code
        $this->status = $status;

        parent::__construct($message, $code, $previous);
    }
}
