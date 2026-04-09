<?php

namespace classes;

use \Exception;

class videoException extends \Exception
{
    private static $mapperErrorCode = [
        "1" => "Video not found",
    ];
    public function __construct($message, $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function getMapperErrorCode(): ?int
    {
        if (array_key_exists($this->code, self::$mapperErrorCode)) {
            return $this->code;
        }
        return null;
    }
}
