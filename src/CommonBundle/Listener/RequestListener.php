<?php


namespace CommonBundle\Listener;


use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;

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
        elseif (preg_match('/api/', $event->getRequest()->getPathInfo()))
        {
            $response = new Response("'country' header missing from request (iso3 code).", Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
        }
    }
}