<?php

declare(strict_types=1);

namespace Exception;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ConstraintViolationException extends RuntimeException
{
    private $list;

    /**
     * @param ConstraintViolationListInterface|ConstraintViolationInterface $value
     */
    public function __construct($value)
    {
        if ($value instanceof ConstraintViolationListInterface) {
            $this->list = $value;
        } elseif ($value instanceof ConstraintViolationInterface) {
            $this->list = new ConstraintViolationList([$value]);
        } else {
            throw new InvalidArgumentException(
                'Argument 1 must be instance of ' . ConstraintViolationListInterface::class . ' or ' . ConstraintViolationInterface::class
            );
        }

        parent::__construct((string) $this->list);
    }

    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->list;
    }
}
