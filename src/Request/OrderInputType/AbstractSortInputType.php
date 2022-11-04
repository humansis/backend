<?php

declare(strict_types=1);

namespace Request\OrderInputType;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractSortInputType implements SortInputTypeInterface
{
    private array $sort = [];

    /**
     * @return array list of accepted sort names
     */
    abstract protected function getValidNames(): array;

    public function setOrderBy($sorts)
    {
        if (!is_array($sorts)) {
            throw new BadRequestHttpException('Invalid sort definition. Array is required');
        }

        $validNames = $this->getValidNames();

        foreach ($sorts as $value) {
            if (!str_contains((string) $value, '.')) {
                $name = $value;
                $direction = 'asc';
            } else {
                [$name, $direction] = explode('.', (string) $value);
            }


            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                throw new BadRequestHttpException("Invalid sort direction for '{$direction}'");
            }

            if (!in_array($name, $validNames)) {
                throw new BadRequestHttpException("Invalid sort name for '{$name}'");
            }

            $this->sort[$name] = $direction;
        }
    }

    public function toArray(): array
    {
        return $this->sort;
    }

    public function has(string $name): bool
    {
        return isset($this->sort[$name]);
    }
}
