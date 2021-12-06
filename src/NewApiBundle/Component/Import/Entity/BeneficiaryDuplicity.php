<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Component\Import\Enum\DuplicityState;
use UserBundle\Entity\User;

/**
 * Imformation about duplicity between queue record and beneficiary.
 *
 * @ORM\Table(name="import_beneficiary_duplicity")
 * @ORM\Entity(repositoryClass="NewApiBundle\Component\Import\Repository\BeneficiaryDuplicityRepository")
 */
class BeneficiaryDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var Queue
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Queue", inversedBy="duplicities")
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
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
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

    public function __construct(Queue $ours, Household $theirs)
    {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = DuplicityState::DUPLICITY_CANDIDATE;
        $this->reasons = [];
    }

    /**
     * @return Queue
     */
    public function getOurs(): Queue
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
     * @return string one of DuplicityState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of DuplicityState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, DuplicityState::values())) {
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
