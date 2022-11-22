<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

trait DateIntervalFilterTrait
{
    /**
     * @Iso8601
     */
    protected $dateFrom;

    /**
     * @Iso8601
     */
    protected $dateTo;

    /**
     * @return string
     */
    public function getDateFrom(): string
    {
        return $this->dateFrom;
    }

    public function hasDateFrom(): bool
    {
        return $this->has('dateFrom');
    }

    /**
     * @return string
     */
    public function getDateTo(): string
    {
        return $this->dateTo;
    }

    public function hasDateTo(): bool
    {
        return $this->has('dateTo');
    }
}
