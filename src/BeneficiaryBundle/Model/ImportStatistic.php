<?php

namespace BeneficiaryBundle\Model;

class ImportStatistic
{
    /**
     * @var int
     */
    private $nbAdded = 0;

    /**
     * @var array
     */
    private $incompleteLine = [];


    /**
     * @return int
     */
    public function getNbAdded(): int
    {
        return $this->nbAdded;
    }

    /**
     * @param int $nbAdded
     * @return ImportStatistic
     */
    public function setNbAdded(int $nbAdded)
    {
        $this->nbAdded = $nbAdded;

        return $this;
    }

    /**
     * @return ImportStatistic
     */
    public function incrementNbAdded()
    {
        $this->nbAdded++;

        return $this;
    }

    /**
     * @return array
     */
    public function getIncompleteLine(): array
    {
        return $this->incompleteLine;
    }

    /**
     * @param IncompleteLine $incompleteLine
     */
    public function addIncompleteLine(IncompleteLine $incompleteLine)
    {
        $this->incompleteLine[] =  $incompleteLine;
    }
}