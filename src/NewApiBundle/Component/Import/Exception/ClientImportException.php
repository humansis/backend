<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientImportException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(400, $message);
    }
}
