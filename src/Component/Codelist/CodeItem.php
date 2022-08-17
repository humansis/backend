<?php
declare(strict_types=1);

namespace Component\Codelist;

class CodeItem implements \JsonSerializable
{
    private $code;

    private $value;

    public function __construct($code, $value)
    {
        $this->code = $code;
        $this->value = $value;
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
