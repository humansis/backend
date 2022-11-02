<?php

declare(strict_types=1);

namespace Request\FilterInputType;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractFilterInputType implements FilterInputTypeInterface
{
    private array $filter = [];

    public function setFilter($filter)
    {
        if (!is_array($filter)) {
            throw new BadRequestHttpException('Invalid filter definition. Array is required');
        }

        foreach ($filter as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new BadRequestHttpException($key . ' is not valid filter name');
            }

            $value = $this->recursiveNormalize($value);

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

    private function recursiveNormalize($value)
    {
        if (is_array($value)) {
            foreach ($value as $i => $v) {
                $value[$i] = $this->recursiveNormalize($v);
            }
        } elseif (is_numeric($value) && !is_int($value)) {
            if (strlen($value) > 1 && str_starts_with($value, '0')) {
                // no transformation for "numbers" like "007"
            } elseif (ctype_digit($value)) {
                $value = (int) $value;
            } else {
                $value = (float) $value;
            }
        }

        return $value;
    }
}
