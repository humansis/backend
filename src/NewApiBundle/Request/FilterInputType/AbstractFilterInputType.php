<?php

declare(strict_types=1);

namespace NewApiBundle\Request\FilterInputType;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractFilterInputType implements FilterInputTypeInterface
{
    private $filter = [];

    public function setFilter($filter)
    {
        if (!is_array($filter)) {
            throw new BadRequestHttpException('Invalid filter definition. Array is required');
        }

        foreach ($filter as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new BadRequestHttpException($key.' is not valid filter name');
            }

            $this->$key = $value;
            $this->filter[$key] = true;
        }
    }

    final protected function has(string $key): bool
    {
        return array_key_exists($key, $this->filter);
    }

    final protected function get(string $key)
    {
        if (array_key_exists($key, $this->filter)) {
            return $this->filter[$key];
        }

        return null;
    }
}
