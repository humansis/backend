<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

class EventCollector
{
    /** @var Event[] */
    private $events = [];

    public function collect(EventProviderInterface $eventProvider): void
    {
        $this->events = array_merge($this->events, $eventProvider->getEvents());
    }

    /**
     * @return iterable|Event[]
     */
    public function getSortedEvents(): iterable
    {
        // TODO: sort
        return $this->events;
    }
}
