<?php

declare(strict_types=1);

namespace Entity;

use Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Information about duplicity between queue record and beneficiary.
 *
 * @ORM\Entity(repositoryClass="\Repository\ImportBeneficiaryDuplicityRepository")
 */
class ImportBeneficiaryDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private array $reasons;

    public function __construct(
        /**
         * @ORM\ManyToOne(targetEntity="Entity\ImportHouseholdDuplicity")
         */
        private ImportHouseholdDuplicity $householdDuplicity,
        /**
         * @ORM\ManyToOne(targetEntity="Entity\ImportQueue")
         */
        private ImportQueue $queue,
        /**
         * @ORM\Column(type="integer")
         */
        private int $memberIndex,
        /**
         * @ORM\ManyToOne(targetEntity="Entity\Beneficiary")
         */
        private Beneficiary $beneficiary
    ) {
        $this->reasons = [];
    }

    public function getQueue(): ImportQueue
    {
        return $this->queue;
    }

    public function getMemberIndex(): int
    {
        return $this->memberIndex;
    }

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    public function getHouseholdDuplicity(): ImportHouseholdDuplicity
    {
        return $this->householdDuplicity;
    }

    /**
     * @return string[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    public function addReason(array $reason): void
    {
        $this->reasons[] = $reason;
    }
}
