<?php

declare(strict_types=1);

namespace Request;

use InvalidArgumentException;

class Pagination
{
    public const DEFAULT_SIZE = PHP_INT_MAX;

    /** @var int|null */
    private $page;

    /** @var int|null */
    private $size;

    public function __construct(?int $page, ?int $size)
    {
        if (null !== $page && $page < 1) {
            throw new InvalidArgumentException('Argument 1 must be greater than zero');
        }

        if (null !== $size && $size < 1) {
            throw new InvalidArgumentException('Argument 2 must be greater than zero');
        }

        $this->page = $page;
        $this->size = $size;
    }

    public function getPage(): int
    {
        return $this->page ?? 1;
    }

    public function getSize(): int
    {
        return $this->size ?? self::DEFAULT_SIZE;
    }

    public function getLimit(): int
    {
        return $this->getSize();
    }

    public function getOffset(): int
    {
        return $this->getSize() * ($this->getPage() - 1);
    }
}
