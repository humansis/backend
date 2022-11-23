<?php

declare(strict_types=1);

namespace Listener;

use GuzzleHttp\Psr7\Response;
use Exception\ConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ErrorResponseEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function __construct(protected LoggerInterface $logger, protected $debug = false)
    {
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ConstraintViolationException) {
            $errors = [];
            foreach ($exception->getErrors() as $error) {
                $errors[] = [
                    'message' => $error->getMessage(),
                    'source' => $error->getPropertyPath(),
                ];
            }
            $data = [
                'code' => 400,
                'errors' => $errors,
            ];
        } elseif ($exception instanceof ConstraintViolationInterface) {
            $data = [
                'code' => 400,
                'errors' => [
                    [
                        'message' => $exception->getMessage(),
                        'source' => $exception->getPropertyPath(),
                    ],
                ],
            ];
        } elseif ($exception instanceof HttpExceptionInterface) {
            if ($exception instanceof BadRequestHttpException) {
                $message = $exception->getMessage();
            } else {
                $message = (new Response($exception->getStatusCode()))->getReasonPhrase();
            }

            $data = [
                'code' => $exception->getStatusCode(),
                'errors' => [
                    'message' => $message,
                ],
            ];
        } else {
            $data = [
                'code' => 500,
                'errors' => [
                    'message' => (new Response(500))->getReasonPhrase(),
                ],
            ];
        }

        $flattenException = FlattenException::createFromThrowable($exception);

        if ($this->debug) {
            $data['debug'] = $flattenException->toArray();
        }

        $this->logger->error($exception->getMessage(), $flattenException->toArray());

        $event->setResponse(new JsonResponse($data, $data['code']));
    }
    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [\Symfony\Component\HttpKernel\KernelEvents::EXCEPTION => ['onKernelException', -100]];
    }
}
