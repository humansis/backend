<?php

declare(strict_types=1);

namespace Component\Codelist;

use JsonSerializable;

class CodeItem implements JsonSerializable
{
    public function __construct(private $code, private $value)
    {
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            'code' => (string) $this->code,
            'value' => (string) $this->value,
        ];
    }
}
