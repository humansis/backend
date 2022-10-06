<?php

declare(strict_types=1);

namespace Component\Import\InvalidCell\ColumnSpecific;

interface ColumnSpecific
{
    public function getColumn(): string;

    /**
     * function($value, string $type)
     *
     * @return callable
     */
    public function getValueCallback(): callable;
}
