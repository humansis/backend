<?php

declare(strict_types=1);

namespace Request\FormatInputType;

interface FormatInputTypeInterface
{
    public function setType(string $type);

    public function getType(): string;
}
