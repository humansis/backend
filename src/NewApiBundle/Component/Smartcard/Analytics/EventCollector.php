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

    public function add(Event $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return iterable|Event[]
     */
    public function getSortedEvents(): iterable
    {
        usort($this->events, function (Event $a, Event $b) {
            return $a->getWhen()->getTimestamp() - $b->getWhen()->getTimestamp();
        });
        return $this->events;
    }
}
