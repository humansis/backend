<?php

declare(strict_types=1);

namespace Listener;

use GuzzleHttp\Psr7\Response;
use Exception\ConstraintViolationException;
use Exception\HandledException;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ErrorResponseListener
{
    /** @var LoggerInterface */
    protected $logger;

    protected $debug;

    public function __construct(LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
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
                'errors' => [[
                    'message' => $exception->getMessage(),
                    'source' => $exception->getPropertyPath(),
                ]],
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

        $flattenException = FlattenException::create($exception);

        if ($this->debug) {
            $data['debug'] = $flattenException->toArray();
        }

        $this->logger->error($exception->getMessage(), $flattenException->toArray());

        $event->setResponse(JsonResponse::create($data, $data['code']));
    }
}
