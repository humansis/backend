<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Component\Import;

class BeneficiaryFactory
{
    /** @var Import\Identity\ComparatorService */
    private $comparator;

    /**
     * @param Identity\ComparatorService $comparator
     */
    public function __construct(Identity\ComparatorService $comparator)
    {
        $this->comparator = $comparator;
    }

    public function create(Import\Integrity\ImportLine $importLine): Import\Domain\Beneficiary
    {
        return new Import\Domain\Beneficiary($importLine, $this->comparator);
    }
}
