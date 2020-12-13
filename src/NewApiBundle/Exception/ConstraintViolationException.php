<?php

namespace NewApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ConstraintViolationException extends \RuntimeException
{
    private $list;

    public function __construct(ConstraintViolationListInterface $list)
    {
        parent::__construct();

        $this->list = $list;
    }

    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->list;
    }
}
