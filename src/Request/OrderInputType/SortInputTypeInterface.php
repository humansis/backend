<?php
declare(strict_types=1);

namespace Request\OrderInputType;

interface SortInputTypeInterface
{
    /**
     * Setter for sort definition.
     *
     * @param mixed $sorts
     */
    public function setOrderBy($sorts);

    /**
     * Returns formatted and normalized list of sort values.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Checks if sort name is requested
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;
}
