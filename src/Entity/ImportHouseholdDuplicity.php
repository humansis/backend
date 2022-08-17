<?php declare(strict_types=1);

namespace Entity;

use Entity\Household;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ImportDuplicityState;
use Entity\User;

/**
 * Information about duplicity between queue record and household.
 *
 * @ORM\Entity(repositoryClass="\Repository\ImportHouseholdDuplicityRepository")
 */
class ImportHouseholdDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importBeneficiaryDuplicities")
     */
    private $ours;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="Entity\Household")
     */
    private $theirs;

    /**
     * @var ImportBeneficiaryDuplicity[]
     *
     * @ORM\OneToMany(targetEntity="Entity\ImportBeneficiaryDuplicity", mappedBy="householdDuplicity", cascade={"persist", "remove"})
     */
    private $beneficiaryDuplicities;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_import_duplicity_state", nullable=false)
     */
    private $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="importBeneficiaryDuplicities")
     */
    private $decideBy;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=true)
     */
    private $decideAt;

    public function __construct(ImportQueue $ours, Household $theirs)
    {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = ImportDuplicityState::DUPLICITY_CANDIDATE;
        $this->beneficiaryDuplicities = new ArrayCollection();
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
     * @return ArrayCollection|ImportBeneficiaryDuplicity[]
     */
    public function getBeneficiaryDuplicities()
    {
        return $this->beneficiaryDuplicities;
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

}
