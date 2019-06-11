<?php


namespace DistributionBundle\Model;

use JMS\Serializer\Annotation\Groups;

class Criteria
{

    /**
     * @var string $tableString
     * @Groups({"Criteria"})
     *
     */
    protected $tableString;

    /**
     * @return string
     */
    public function getTableString(): string
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
    * @var string $target
    * @Groups({"Criteria"})
    *
    */
    protected $target;

    /**
     * @return string
     */
    public function getTarget(): string
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
