<?php

declare(strict_types=1);

namespace Listener;

use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\Tracing\TransactionContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function Sentry\configureScope;
use function Sentry\startTransaction;

class SentrySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @param ControllerEvent $event
     * @return void
     */
    public function onKernelController(ControllerEvent $event)
    {
        $request = $event->getRequest();
        $parsedUrl = parse_url($request->getUri());
        $transaction = SentrySdk::getCurrentHub()->getTransaction();
        $transaction->setOp('api.request');
        $transaction->setTags([
            'module' => $this->getModuleName($request->getUri()),
            'method' => $request->getRealMethod(),
        ]);
        $transaction->setData([
            'url' => $request->getUri(),
            'query_string' => key_exists('query', $parsedUrl) ? $parsedUrl['query'] : null,
        ]);
        configureScope(function (Scope $scope): void {
            $scope->setUser($this->getUserInfo());
        });
    }

    /**
     * @param $uri
     * @return string|null
     */
    private function getModuleName($uri): string|null
    {
        if (preg_match('/(?<module>[^\/]+-app)\//', $uri, $matches)) {
            return $matches['module'];
        }
        return null;
    }

    /**
     * @return array
     */
    private function getUserInfo(): array
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }
        if (!$token->getUser()) {
            return [];
        }
        return [
            'id' => $token->getUser()->getId(),
            'username' => $token->getUser()->getUserIdentifier(),
            'roles' => $token->getRoleNames()
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
