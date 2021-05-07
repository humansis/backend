<?php

namespace NewApiBundle\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class VaryingListener
{
    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        // only new api requests are supported
        if (false === $event->getRequest()->attributes->get('disable-common-request-listener', false)) {
            return;
        }

        // it define country header as important for cache processing
        if ($event->getRequest()->headers->has('country')) {
            $event->getResponse()->setVary('country', false);
        }
    }
}
