<?php
declare(strict_types=1);

namespace NewApiBundle\Request\OrderInputType;

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
}
