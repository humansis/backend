<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Component\Import;
use NewApiBundle\Entity\ImportQueue;

class HouseholdFactory
{
    /** @var Import\Identity\ComparatorService */
    private $comparator;
    /** @var BeneficiaryFactory */
    private $beneficiaryFactory;
    /** @var Import\Integrity\ImportLineFactory */
    private $lineFactory;
    /** @var Import\Finishing\HouseholdDecoratorBuilder */
    private $builder;

    /**
     * @param Identity\ComparatorService          $comparator
     * @param BeneficiaryFactory                  $beneficiaryFactory
     * @param Integrity\ImportLineFactory         $lineFactory
     * @param Finishing\HouseholdDecoratorBuilder $builder
     */
    public function __construct(
        Identity\ComparatorService                 $comparator,
        BeneficiaryFactory                         $beneficiaryFactory,
        Integrity\ImportLineFactory                $lineFactory,
        Import\Finishing\HouseholdDecoratorBuilder $builder
    ) {
        $this->comparator = $comparator;
        $this->beneficiaryFactory = $beneficiaryFactory;
        $this->lineFactory = $lineFactory;
        $this->builder = $builder;
    }

    public function create(ImportQueue $importQueue): Import\Domain\Household
    {
        return new Import\Domain\Household(
            $importQueue,
            $this->comparator,
            $this->beneficiaryFactory,
            $this->lineFactory,
            $this->builder
        );
    }
}
