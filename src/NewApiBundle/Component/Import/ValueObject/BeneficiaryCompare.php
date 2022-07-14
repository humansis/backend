<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

use NewApiBundle\Entity;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;

class BeneficiaryCompare
{
    /**
     * @var Integrity\ImportLine
     */
    private $importLine;

    /**
     * @var Entity\Beneficiary
     */
    private $beneficiary;

    /** @var ImportBeneficiaryDuplicity */
    private $beneficiaryDuplicity;

    /**
     * @param Integrity\ImportLine       $importLine
     * @param Entity\Beneficiary         $beneficiary
     * @param ImportBeneficiaryDuplicity $beneficiaryDuplicity
     */
    public function __construct(Integrity\ImportLine                            $importLine, Entity\Beneficiary $beneficiary,
                                ImportBeneficiaryDuplicity $beneficiaryDuplicity
    )
    {
        $this->importLine = $importLine;
        $this->beneficiary = $beneficiary;
        $this->beneficiaryDuplicity = $beneficiaryDuplicity;
    }

    /**
     * @return Integrity\ImportLine
     */
    public function getImportLine(): Integrity\ImportLine
    {
        return $this->importLine;
    }

    /**
     * @return Entity\Beneficiary
     */
    public function getBeneficiary(): Entity\Beneficiary
    {
        return $this->beneficiary;
    }

    /**
     * @return ImportBeneficiaryDuplicity
     */
    public function getBeneficiaryDuplicity(): ImportBeneficiaryDuplicity
    {
        return $this->beneficiaryDuplicity;
    }

}
