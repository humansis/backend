<?php

declare(strict_types=1);

namespace Pagination;

use InvalidArgumentException;
use JsonSerializable;

class Paginator implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $totalCount;

    private readonly int $page;

    /**
     * @param int|null $totalCount
     * @param int $page
     */
    public function __construct(private readonly iterable $data, $totalCount = null, $page = 1)
    {
        if ($page <= 0) {
            throw new InvalidArgumentException('Page must be greater than zero');
        }
        $this->totalCount = $totalCount ?? count($data);
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'totalCount' => $this->totalCount,
            'data' => $this->data,
        ];
    }
}
