<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

interface EventProviderInterface
{
    /**
     * @return Event[]
     */
    public function getEvents(): array;
}
