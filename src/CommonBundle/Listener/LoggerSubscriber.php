<?php

namespace CommonBundle\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoggerSubscriber implements EventSubscriberInterface
{

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }


    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->logger->error('request', [$event->getRequest()->getUri()]);
        $this->logger->error('request headers', $event->getRequest()->headers->all());
        $this->logger->error('request query', $event->getRequest()->query->all());
        $this->logger->error('request body', $event->getRequest()->request->all());
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $this->logger->error('response request', [$event->getRequest()->getUri()]);
        $this->logger->error('response headers', $event->getResponse()->headers->all());
        $this->logger->error('response content', [$event->getResponse()->getContent()]);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->logger->error('exception request', [$event->getRequest()->getUri()]);
        if ($event->getResponse()) {
            $this->logger->error('exception response', [$event->getResponse()->getContent()]);
        }
        $this->logger->error('exception: '.$event->getException()->getMessage(), $event->getException()->getTrace());
    }
}
