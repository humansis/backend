<?php

namespace Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function __construct(private array $corsConfiguration)
    {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $method  = $request->getRealMethod();
        if ('OPTIONS' === strtoupper($method)) {
            $response = new Response();
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }


        $response = $event->getResponse();
        $origin = $event->getRequest()->headers->get('Origin', null);
        if (in_array($origin, $this->corsConfiguration['allow_origin'])) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Headers', join(', ', $this->corsConfiguration['allow_headers']));
            $response->headers->set('Access-Control-Expose-Headers', $this->corsConfiguration['expose_headers']);
            $response->headers->set('Access-Control-Allow-Credentials', $this->corsConfiguration['allow_credentials']);
            $response->headers->set('Access-Control-Allow-Methods', join(', ', $this->corsConfiguration['allow_methods']));
            $response->headers->set('Access-Control-Max-Age', $this->corsConfiguration['max_age']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            \Symfony\Component\HttpKernel\KernelEvents::REQUEST => 'onKernelRequest',
            \Symfony\Component\HttpKernel\KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }
}
