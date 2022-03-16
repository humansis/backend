<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

use BeneficiaryBundle\Entity;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;

class HouseholdCompare
{
    /**
     * @var Integrity\ImportLine
     */
    private $importLine;

    /**
     * @var Entity\Household
     */
    private $household;

    /**
     * @param Integrity\ImportLine $importLine
     * @param Entity\Household     $household
     */
    public function __construct(Integrity\ImportLine $importLine, Entity\Household $household)
    {
        $this->importLine = $importLine;
        $this->household = $household;
    }

    /**
     * @return Integrity\ImportLine
     */
    public function getImportLine(): Integrity\ImportLine
    {
        return $this->importLine;
    }

    /**
     * @return Entity\Household
     */
    public function getHousehold(): Entity\Household
    {
        return $this->household;
    }

}
