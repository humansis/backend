<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Enum\ImportDuplicityState;
use UserBundle\Entity\User;

/**
 * Information about duplicity between queue record and beneficiary.
 *
 * @ORM\Entity()
 */
class ImportBeneficiaryDuplicity
{
    /**
     * @var ImportQueue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportQueue")
     */
    private $queue;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $memberIndex;

    /**
     * @var Beneficiary
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $reasons;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $differences;


    public function __construct(ImportQueue $ours, int $memberIndex, Beneficiary $theirs)
    {
        $this->queue = $ours;
        $this->beneficiary = $theirs;
        $this->reasons = [];
        $this->differences = [];
        $this->memberIndex = $memberIndex;
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
     * @return string[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * @param string $reason
     */
    public function addReason(string $reason): void
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
     * @param string $property
     * @param string $difference
     */
    public function addDifference(string $property, string $difference): void
    {
        $this->differences[$property] = $difference;
    }

}
