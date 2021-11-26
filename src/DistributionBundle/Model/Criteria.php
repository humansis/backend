<?php


namespace DistributionBundle\Model;



class Criteria
{

    /**
     * @var string $tableString
     *
     *
     */
    protected $tableString;

    /**
    * @var string $target
    *
    *
    */
    protected $target;

    /**
     * @return string
     */
    public function getTableString(): ?string
    {
        return $this->tableString;
    }

    /**
     * @param string $tableString
     * @return Criteria
     */
    public function setTableString(string $tableString)
    {
        $this->tableString = $tableString;

        return $this;
    }


    /**
     * @return string
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return Criteria
     */
    public function setTarget(string $target)
    {
        $this->target = $target;

        return $this;
    }
}
