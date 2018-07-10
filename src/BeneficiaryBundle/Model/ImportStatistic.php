<?php

namespace BeneficiaryBundle\Model;

class ImportStatistic
{
    /**
     * @var int
     */
    private $nbAdded = 0;

    /**
     * @var int
     */
    private $nbIncomplete = 0;

    /**
     * @var int
     */
    private $nbDuplicates = 0;


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
     * @return int
     */
    public function getNbIncomplete(): int
    {
        return $this->nbIncomplete;
    }

    /**
     * @param int $nbIncomplete
     * @return ImportStatistic
     */
    public function setNbIncomplete(int $nbIncomplete)
    {
        $this->nbIncomplete = $nbIncomplete;

        return $this;
    }

    /**
     * @return ImportStatistic
     */
    public function incrementNbIncomplete()
    {
        $this->nbIncomplete++;

        return $this;
    }

    /**
     * @return int
     */
    public function getNbDuplicates(): int
    {
        return $this->nbDuplicates;
    }

    /**
     * @param int $nbDuplicates
     * @return ImportStatistic
     */
    public function setNbDuplicates(int $nbDuplicates)
    {
        $this->nbDuplicates = $nbDuplicates;

        return $this;
    }

    /**
     * @return ImportStatistic
     */
    public function incrementNbDuplicates()
    {
        $this->nbDuplicates++;

        return $this;
    }
}