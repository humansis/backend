<?php

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Validator\Constraints\Enum;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateReliefPackageInputType implements InputTypeInterface
{

    /**
     * @Enum(enumClass="Enum\ReliefPackageState")
     * @var string
     */
    private $state;

    /**
     * @Assert\Type(type="scalar")
     */
    private $amountDistributed;

    /**
     *@Assert\Type(type="string")
     */
    private $notes;

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getAmountDistributed()
    {
        return $this->amountDistributed;
    }

    /**
     * @param mixed $amountDistributed
     */
    public function setAmountDistributed($amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param mixed $notes
     */
    public function setNotes($notes): void
    {
        $this->notes = $notes;
    }



}
