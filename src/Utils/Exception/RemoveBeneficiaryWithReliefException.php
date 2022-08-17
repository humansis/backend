<?php
declare(strict_types=1);

namespace Utils\Exception;

use Entity\Beneficiary;
use Symfony\Component\Validator\ConstraintViolationInterface;

class RemoveBeneficiaryWithReliefException extends \InvalidArgumentException implements ConstraintViolationInterface
{
    /** @var Beneficiary */
    protected $beneficiary;

    protected $atPath;

    public function __construct(Beneficiary $beneficiary)
    {
        parent::__construct();

        $this->beneficiary = $beneficiary;
        $this->message = strtr($this->getMessageTemplate(), $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageTemplate()
    {
        return 'Beneficiary {{ name }} can\'t be removed from assistance. He has already received a relief.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return ['{{ name }}' => $this->beneficiary->getLocalGivenName().' '.$this->beneficiary->getLocalFamilyName()];
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
        return $this->beneficiary;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidValue()
    {
        return $this->beneficiary;
    }
}
