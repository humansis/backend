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
        // return error response if api request (i.e. not profiler or doc) or login routes (for api tester)
        elseif (preg_match('/api/', $event->getRequest()->getPathInfo()) &&
                !preg_match('/api\/(login || salt)/', $event->getRequest()->getPathInfo()))
        {
            $response = new Response("'country' header missing from request (iso3 code).", Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
        }
    }
}