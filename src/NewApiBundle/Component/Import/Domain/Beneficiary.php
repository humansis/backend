<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Domain;

use NewApiBundle\Component\Import;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;

class Beneficiary
{
    /** @var Import\Integrity\ImportLine */
    private $lineRoot;

    /** @var Import\Identity\ComparatorService */
    private $comparator;

    /**
     * @param Import\Integrity\ImportLine       $lineRoot
     * @param Import\Identity\ComparatorService $comparator
     */
    public function __construct(Import\Integrity\ImportLine $lineRoot, Import\Identity\ComparatorService $comparator)
    {
        $this->lineRoot = $lineRoot;
        $this->comparator = $comparator;
    }

    public function compare(\BeneficiaryBundle\Entity\Beneficiary $beneficiaryEntity): Import\ValueObject\BeneficiaryCompare
    {
        return $this->comparator->compareBeneficiaries($this, $beneficiaryEntity);
    }

    public function getInputType(): BeneficiaryInputType
    {
        $builder = new Import\Finishing\BeneficiaryDecoratorBuilder();
        return $builder->buildBeneficiaryInputType($this->lineRoot);
    }
}
