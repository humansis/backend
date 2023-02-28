<?php

namespace Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class VaryingEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $varyHeaders = ['country', 'origin', 'accept-language'];

        if (count($varyHeaders) > 0) {
            $event->getResponse()->setVary(join(', ', $varyHeaders), false);
        }
    }
    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [\Symfony\Component\HttpKernel\KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}
