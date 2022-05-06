<?php declare(strict_types=1);

namespace NewApiBundle\Component\Http;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class HeaderCountryException extends BadRequestHttpException
{
    private const MISSING_MESSAGE = 'Missing header attribute country';

    public function __construct(?string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        if (empty($message)) {
            $message = self::MISSING_MESSAGE;
        }
        parent::__construct($message, $previous, $code, $headers);
    }
}
