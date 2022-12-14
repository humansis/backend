<?php

declare(strict_types=1);

namespace Component\Assistance;

use DateTimeImmutable;
use Entity\Assistance\ReliefPackage;
use Enum\CacheTarget;
use Enum\ReliefPackageState;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Contracts\Cache\CacheInterface;
use Workflow\ReliefPackageTransitions;

class ReliefPackageSubscriber implements EventSubscriberInterface
{
    final public const GUARD_CODE_NOT_COMPLETE = '6a5c82b7-6d68-4712-a7c8-e093b7afd8d2';
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // every successful change state
            'workflow.reliefPackage.entered' => ['clearAssistanceStatisticCache'],
            'workflow.reliefPackage.entered.' . ReliefPackageState::DISTRIBUTED => ['markAsDistributed'],
            'workflow.reliefPackage.guard.' . ReliefPackageTransitions::REUSE => [
                ['guardNotDistributed', 0],
            ],
        ];
    }

    public function markAsDistributed(EnteredEvent $event)
    {
        /** @var ReliefPackage $reliefPackage */
        $reliefPackage = $event->getSubject();
        $reliefPackage->setDistributedAt(new DateTimeImmutable());
    }

    public function clearAssistanceStatisticCache(Event $event): void
    {
        /** @var ReliefPackage $reliefPackage */
        $reliefPackage = $event->getSubject();
        try {
            $this->cache->delete(
                CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId())
            );
        } catch (InvalidArgumentException) {
        }
    }

    public function guardNotDistributed(GuardEvent $guardEvent): void
    {
        /** @var ReliefPackage $reliefPackage */
        $reliefPackage = $guardEvent->getSubject();

        $hasDistributedMoney = (double)$reliefPackage->getAmountDistributed() > 0.0;

        if ($hasDistributedMoney) {
            $guardEvent->addTransitionBlocker(
                new TransitionBlocker('Relief package has money distributed already', static::GUARD_CODE_NOT_COMPLETE)
            );
        }
    }
}
