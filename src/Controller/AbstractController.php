<?php

declare(strict_types=1);

namespace Controller;

use Serializer\MapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractController extends Controller
{

    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $context = []): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if (!isset($context[MapperInterface::NEW_API])) {
            $context[MapperInterface::NEW_API] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getCountryCode(Request $request): string {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }
        return $countryIso3;
    }

    /**
     * @param string $filename
     *
     * @return Response
     */
    protected function exportResponse($filename): Response {
        try{
            set_time_limit(600);
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isGuesserSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);
            return $response;
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }
}
