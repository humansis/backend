<?php

namespace Listener;

use Sentry\SentrySdk;
use Sentry\Tracing\TransactionContext;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

use function Sentry\startTransaction;

class SentrySubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function __construct()
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
        $request = $event->getRequest();
        $uri = $this->normalizeUri($request->getRequestUri());
        $requestMethod = $request->getMethod();
        $transactionContext = new TransactionContext("{$requestMethod} {$uri}");
        $transaction = startTransaction($transactionContext);
        $transaction->setName("{$requestMethod} {$uri}");
        $transaction->setOp('api.request');
        SentrySdk::getCurrentHub()->setSpan($transaction);
    }

    /**
     * It will remove all integer ID number from URL and replace them by id placeholder
     *
     * @param string $uri
     * @return string
     */
    private function normalizeUri(string $uri): string
    {
        $uriWithPlaceholder = preg_replace('/\/(\d)+\//', '/{id}/', $uri);
        return preg_replace('/\?.+/', '', $uriWithPlaceholder);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            \Symfony\Component\HttpKernel\KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
