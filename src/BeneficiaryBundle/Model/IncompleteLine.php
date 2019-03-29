<?php


namespace BeneficiaryBundle\Model;

class IncompleteLine
{
    /** @var int */
    private $lineIncomplete;


    public function __construct($lineIncomplete)
    {
        $this->setLineIncomplete($lineIncomplete);
    }

    /**
     * @return int
     */
    public function getLineIncomplete(): int
    {
        return $this->lineIncomplete;
    }

    /**
     * @param int $lineIncomplete
     */
    public function setLineIncomplete(int $lineIncomplete)
    {
        $this->lineIncomplete = $lineIncomplete;
    }
}
