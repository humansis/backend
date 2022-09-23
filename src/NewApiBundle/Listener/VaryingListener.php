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

        $varyHeaders = ['country', 'origin', 'accept-language'];

        if (count($varyHeaders) > 0) {
            $event->getResponse()->setVary(join(', ', $varyHeaders), false);
        }
    }
}