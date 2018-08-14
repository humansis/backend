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
     * @var string $distributionType
     * @Groups({"Criteria"})
     *
     */
    protected $distributionType;

    /**
     * @return string
     */
    public function getDistributionType(): string
    {
        return $this->distributionType;
    }

    /**
     * @param string $distributionType
     * @return Criteria
     */
    public function setDistributionType(string $distributionType)
    {
        $this->distributionType = $distributionType;

        return $this;
    }


}