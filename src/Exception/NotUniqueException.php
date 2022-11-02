<?php

declare(strict_types=1);

namespace Exception;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationInterface;

class NotUniqueException extends RuntimeException implements ConstraintViolationInterface
{
    public function __construct(protected $value, protected $atPath = null)
    {
        parent::__construct();
        $this->message = str_replace('{{ value }}', $this->value, (string) $this->getMessageTemplate());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageTemplate()
    {
        return 'Value \'{{ value }}\' already exists';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return ['{{ value }}' => $this->value];
    }

    /**
     * {@inheritdoc}
     */
    public function getPlural()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath()
    {
        return $this->atPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidValue()
    {
        return $this->value;
    }
}
