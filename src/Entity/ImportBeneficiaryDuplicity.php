<?php declare(strict_types=1);

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
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="Entity\ImportQueue")
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
     * @ORM\ManyToOne(targetEntity="Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var ImportHouseholdDuplicity
     *
     * @ORM\ManyToOne(targetEntity="Entity\ImportHouseholdDuplicity")
     */
    private $householdDuplicity;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $reasons;


    public function __construct(ImportHouseholdDuplicity $householdDuplicity, ImportQueue $ours, int $memberIndex, Beneficiary $theirs)
    {
        $this->queue = $ours;
        $this->beneficiary = $theirs;
        $this->reasons = [];
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
}
