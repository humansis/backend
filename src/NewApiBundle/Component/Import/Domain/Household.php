<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Domain;

use BeneficiaryBundle\Entity;
use NewApiBundle\Component\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\InputType\HouseholdCreateInputType;

class Household
{
    /** @var ImportQueue */
    private $queueRoot;

    /** @var Import\Identity\ComparatorService */
    private $comparator;

    /** @var Import\BeneficiaryFactory */
    private $beneficiaryFactory;

    /** @var Import\Integrity\ImportLineFactory */
    private $lineFactory;
    /** @var Import\Finishing\HouseholdDecoratorBuilder */
    private $builder;

    /**
     * @param ImportQueue                                $queueRoot
     * @param Import\Identity\ComparatorService          $comparator
     * @param Import\BeneficiaryFactory                  $beneficiaryFactory
     * @param Import\Integrity\ImportLineFactory         $lineFactory
     * @param Import\Finishing\HouseholdDecoratorBuilder $builder
     */
    public function __construct(
        ImportQueue                                $queueRoot,
        Import\Identity\ComparatorService          $comparator,
        Import\BeneficiaryFactory                  $beneficiaryFactory,
        Import\Integrity\ImportLineFactory         $lineFactory,
        Import\Finishing\HouseholdDecoratorBuilder $builder
    ) {
        $this->queueRoot = $queueRoot;
        $this->comparator = $comparator;
        $this->beneficiaryFactory = $beneficiaryFactory;
        $this->lineFactory = $lineFactory;
        $this->builder = $builder;
    }

    public function compare(Entity\Household $household): Import\ValueObject\HouseholdCompare
    {
        return $this->comparator->compareHouseholds($this, $household);
    }

    public function getInputType(): HouseholdCreateInputType
    {
        return $this->builder->buildHouseholdInputType($this->queueRoot);
    }

    public function getMembers(): iterable
    {
        foreach ($this->lineFactory->createAll($this->queueRoot) as $line) {
            yield $this->beneficiaryFactory->create($line);
        }
    }
}
