<?php
declare(strict_types=1);

namespace Request\FilterInputType;

interface FilterInputTypeInterface
{
    /**
     * Setter for filter definition.
     *
     * @param mixed $filter
     */
    public function setFilter($filter);
}
