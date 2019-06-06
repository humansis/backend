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
    * @var string $kindOfBeneficiary
    * @Groups({"Criteria"})
    *
    */
    protected $kindOfBeneficiary;

    /**
     * @return string
     */
    public function getkindOfBeneficiary(): string
    {
        return $this->kindOfBeneficiary;
    }

    /**
     * @param string $kindOfBeneficiary
     * @return Criteria
     */
    public function setkindOfBeneficiary(string $kindOfBeneficiary)
    {
        $this->kindOfBeneficiary = $kindOfBeneficiary;

        return $this;
    }
}
