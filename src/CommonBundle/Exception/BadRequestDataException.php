<?php
namespace CommonBundle\Exception;

use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

class BadRequestDataException extends \InvalidArgumentException implements RequestExceptionInterface
{

}