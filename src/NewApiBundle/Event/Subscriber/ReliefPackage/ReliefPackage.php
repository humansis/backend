<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ReliefPackage;

use NewApiBundle\Enum\CacheTarget;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Cache\CacheInterface;

class ReliefPackage implements EventSubscriberInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.reliefPackage.entered' => ['clearAssistanceStatisticCache'],
        ];
    }

    /**
     * @param Event $event
     *
     * @return void
     */
    public function clearAssistanceStatisticCache(Event $event): void
    {
        /** @var \NewApiBundle\Entity\Assistance\ReliefPackage $reliefPackage */
        $reliefPackage = $event->getSubject();
        try {
            $this->cache->delete(CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId()));
        } catch (InvalidArgumentException $e) {
        }
    }

}
