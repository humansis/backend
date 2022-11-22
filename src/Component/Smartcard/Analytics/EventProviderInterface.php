<?php

declare(strict_types=1);

namespace Component\Smartcard\Analytics;

interface EventProviderInterface
{
    /**
     * @return Event[]
     */
    public function getEvents(): array;
}
