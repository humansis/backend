<?php

declare(strict_types=1);

namespace Utils\Exception;

use Entity\Beneficiary;
use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationInterface;

class RemoveBeneficiaryWithReliefException extends InvalidArgumentException implements ConstraintViolationInterface
{
    protected $atPath;

    public function __construct(protected Beneficiary $beneficiary)
    {
        parent::__construct();
        $this->message = strtr($this->getMessageTemplate(), $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageTemplate(): string
    {
        return 'Beneficiary {{ name }} can\'t be removed from assistance. He has already received a relief.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            '{{ name }}' => $this->beneficiary->getPerson()->getLocalGivenName() . ' ' . $this->beneficiary->getPerson()->getLocalFamilyName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPlural(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot(): Beneficiary
    {
        return $this->beneficiary;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidValue(): Beneficiary
    {
        return $this->beneficiary;
    }
}
