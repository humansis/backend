<?php

declare(strict_types=1);

namespace NewApiBundle\Listener;

use GuzzleHttp\Psr7\Response;
use NewApiBundle\Exception\ConstraintViolationException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorResponseListener
{
    protected $debug;

    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

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

        } elseif ($exception instanceof HttpExceptionInterface) {
            $data = [
                'code' => $exception->getStatusCode(),
                'errors' => [
                    'message' => (new Response($exception->getStatusCode()))->getReasonPhrase(),
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

        if ($this->debug) {
            $flattenException = FlattenException::create($exception);
            $data['debug'] = $flattenException->toArray();
        }

        $event->setResponse(JsonResponse::create($data, $data['code']));
    }
}
