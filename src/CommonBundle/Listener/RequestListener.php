<?php


namespace CommonBundle\Listener;


use CommonBundle\Controller\BMSController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use UserBundle\Controller\UserController;

class RequestListener
{
    /**
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->headers->has('country'))
        {
            $countryIso3 = $event->getRequest()->headers->get('country');
            $event->getRequest()->request->add(["__country" => $countryIso3]);
        }
    }
}