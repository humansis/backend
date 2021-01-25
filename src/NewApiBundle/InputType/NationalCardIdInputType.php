<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NationalCardIdInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotNull
     */
    private $idNumber;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotNull
     */
    private $idType;

    /**
     * @return string
     */
    public function getIdNumber()
    {
        return $this->idNumber;
    }

    /**
     * @param string $idNumber
     */
    public function setIdNumber($idNumber)
    {
        $this->idNumber = $idNumber;
    }

    /**
     * @return string
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * @param string $idType
     */
    public function setIdType($idType)
    {
        $this->idType = $idType;
    }
}
