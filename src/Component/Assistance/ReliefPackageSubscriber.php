<?php

declare(strict_types=1);

namespace Component\Assistance;

use DateTimeImmutable;
use Entity;
use Enum\CacheTarget;
use Enum\ReliefPackageState;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Cache\CacheInterface;

class ReliefPackageSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // every successful change state
            'workflow.reliefPackage.entered' => ['clearAssistanceStatisticCache'],
            'workflow.reliefPackage.entered.' . ReliefPackageState::DISTRIBUTED => ['markAsDistributed'],
        ];
    }

    public function markAsDistributed(EnteredEvent $event)
    {
        /** @var Entity\Assistance\ReliefPackage $reliefPackage */
        $reliefPackage = $event->getSubject();
        $reliefPackage->setDistributedAt(new DateTimeImmutable());
    }

    public function clearAssistanceStatisticCache(Event $event): void
    {
        /** @var Entity\Assistance\ReliefPackage $reliefPackage */
        $reliefPackage = $event->getSubject();
        try {
            $this->cache->delete(
                CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId())
            );
        } catch (InvalidArgumentException) {
        }
    }
}
