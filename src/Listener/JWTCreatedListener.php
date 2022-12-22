<?php

declare(strict_types=1);

namespace Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = $event->getData();
        $payload['ip'] = $request->getClientIp();
        $payload['environment'] = getenv('ENVIRONMENT');
        $event->setData($payload);
    }


}
