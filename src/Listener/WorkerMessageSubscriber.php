<?php

declare(strict_types=1);

namespace Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;

use function get_class;

#[AsEventListener(event: WorkerMessageFailedEvent::class, method: 'onWorkerMessageFailedEvent')]
#[AsEventListener(event: WorkerMessageHandledEvent::class, method: 'onWorkerMessageHandledEvent')]
#[AsEventListener(event: WorkerMessageReceivedEvent::class, method: 'onWorkerMessageReceivedEvent')]
#[AsEventListener(event: WorkerMessageRetriedEvent::class, method: 'onWorkerMessageRetriedEvent')]
final class WorkerMessageSubscriber
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function onWorkerMessageFailedEvent(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $throwable = $event->getThrowable();

        $this->logger->error(
            "Worker message failed on {$event->getReceiverName()}",
            [
                'messageClass' => get_class($envelope->getMessage()),
                'willRetry' => $event->willRetry(),
                'exceptionMessage' => $throwable->getMessage(),
                'envelopeMessage' => $envelope->getMessage(),
            ]
        );
    }

    public function onWorkerMessageHandledEvent(WorkerMessageHandledEvent $event): void
    {
        $envelope = $event->getEnvelope();

        $this->logger->info(
            "Worker message successfully handled on {$event->getReceiverName()}",
            [
                'messageClass' => get_class($envelope->getMessage()),
                'envelopeMessage' => $envelope->getMessage(),
            ]
        );
    }

    public function onWorkerMessageReceivedEvent(WorkerMessageReceivedEvent $event): void
    {
        $envelope = $event->getEnvelope();

        $this->logger->info(
            "Worker for {$event->getReceiverName()} received message",
            [
                'messageClass' => get_class($envelope->getMessage()),
                'envelopeMessage' => $envelope->getMessage(),
            ]
        );
    }

    public function onWorkerMessageRetriedEvent(WorkerMessageRetriedEvent $event): void
    {
        $envelope = $event->getEnvelope();

        $this->logger->info(
            "Worker for {$event->getReceiverName()} retried event",
            [
                'messageClass' => get_class($envelope->getMessage()),
                'envelopeMessage' => $envelope->getMessage(),
            ]
        );
    }
}
