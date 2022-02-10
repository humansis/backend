<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;

/**
 * Information about duplicity between queue record and beneficiary.
 *
 * @ORM\Entity()
 */
class ImportBeneficiaryDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportQueue")
     */
    private $queue;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $memberIndex;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var ImportHouseholdDuplicity
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportHouseholdDuplicity")
     */
    private $householdDuplicity;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $reasons;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $differences;


    public function __construct(ImportHouseholdDuplicity $householdDuplicity, ImportQueue $ours, int $memberIndex, Beneficiary $theirs)
    {
        $this->queue = $ours;
        $this->beneficiary = $theirs;
        $this->reasons = [];
        $this->differences = [];
        $this->memberIndex = $memberIndex;
        $this->householdDuplicity = $householdDuplicity;
    }


    /**
     * @return ImportQueue
     */
    public function getQueue(): ImportQueue
    {
        return $this->queue;
    }

    /**
     * @return int
     */
    public function getMemberIndex(): int
    {
        return $this->memberIndex;
    }

    /**
     * @return Beneficiary
     */
    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }

    /**
     * @return ImportHouseholdDuplicity
     */
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

    /**
     * @param array $reason
     */
    public function addReason(array $reason): void
    {
        $this->reasons[] = $reason;
    }

    /**
     * @return string[]
     */
    public function getDifferences(): array
    {
        return $this->differences;
    }

    /**
     * @param array $differences
     */
    public function setDifferences(array $differences)
    {
        $this->differences = $differences;
    }

}
