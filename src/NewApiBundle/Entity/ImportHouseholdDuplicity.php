<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Enum\ImportDuplicityState;
use UserBundle\Entity\User;

/**
 * Information about duplicity between queue record and household.
 *
 * @ORM\Table(name="import_beneficiary_duplicity")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ImportBeneficiaryDuplicityRepository")
 */
class ImportHouseholdDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportQueue", inversedBy="importBeneficiaryDuplicities")
     */
    private $ours;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household", inversedBy="importBeneficiaryDuplicities")
     */
    private $theirs;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_import_duplicity_state", nullable=false)
     */
    private $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="importBeneficiaryDuplicities")
     */
    private $decideBy;

    /**
     * @var string[]
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $reasons;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=false)
     */
    private $decideAt;

    public function __construct(ImportQueue $ours, Household $theirs)
    {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = ImportDuplicityState::DUPLICITY_CANDIDATE;
        $this->reasons = [];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ImportQueue
     */
    public function getOurs(): ImportQueue
    {
        return $this->ours;
    }

    /**
     * @return Household
     */
    public function getTheirs(): Household
    {
        return $this->theirs;
    }

    /**
     * @return string one of ImportDuplicityState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of ImportDuplicityState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, ImportDuplicityState::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$state.' is not valid Import duplicity state');
        }

        $this->state = $state;
    }

    /**
     * @param User $decideBy
     */
    public function setDecideBy(User $decideBy): void
    {
        $this->decideBy = $decideBy;
    }

    /**
     * @return User
     */
    public function getDecideBy(): User
    {
        return $this->decideBy;
    }

    /**
     * @param \DateTimeInterface $dateTime
     */
    public function setDecideAt(\DateTimeInterface $dateTime): void
    {
        $this->decideAt = $dateTime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDecideAt(): \DateTimeInterface
    {
        return $this->decideAt;
    }

    /**
     * @return string[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * @param string[] $reasons
     */
    public function setReasons(array $reasons): void
    {
        $this->reasons = $reasons;
    }

    /**
     * @param string $reason
     */
    public function addReason(string $reason): void
    {
        $this->reasons[] = $reason;
    }
}
