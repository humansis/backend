<?php

namespace Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack, private readonly UserRepository $userRepository)
    {
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = $event->getData();
        $payload['ip'] = $request->getClientIp();
        $payload['environment'] = getenv('ENVIRONMENT');
        $event->setData($payload);
    }


}
