<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ImportDuplicityState;
use InvalidArgumentException;

/**
 * Information about duplicity between queue record and household.
 *
 * @ORM\Entity(repositoryClass="\Repository\ImportHouseholdDuplicityRepository")
 */
class ImportHouseholdDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importBeneficiaryDuplicities")
     */
    private ImportQueue $ours;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Household")
     */
    private Household $theirs;

    /**
     * @var ImportBeneficiaryDuplicity[]
     *
     * @ORM\OneToMany(targetEntity="Entity\ImportBeneficiaryDuplicity", mappedBy="householdDuplicity", cascade={"persist", "remove"})
     */
    private array $beneficiaryDuplicities;

    /**
     * @ORM\Column(name="state", type="enum_import_duplicity_state", nullable=false)
     */
    private string $state;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="importBeneficiaryDuplicities")
     */
    private ?User $decideBy = null;

    /**
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=true)
     */
    private ?DateTimeInterface $decideAt = null;

    public function __construct(
        ImportQueue $ours,
        Household $theirs
    ) {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = ImportDuplicityState::DUPLICITY_CANDIDATE;
        $this->beneficiaryDuplicities = [];
    }

    public function getOurs(): ImportQueue
    {
        return $this->ours;
    }

    public function getTheirs(): Household
    {
        return $this->theirs;
    }

    /**
     * @return ArrayCollection|ImportBeneficiaryDuplicity[]
     */
    public function getBeneficiaryDuplicities(): ArrayCollection | array
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
            throw new InvalidArgumentException('Invalid argument. ' . $state . ' is not valid Import duplicity state');
        }

        $this->state = $state;
    }

    public function setDecideBy(User $decideBy): void
    {
        $this->decideBy = $decideBy;
    }

    public function getDecideBy(): User
    {
        return $this->decideBy;
    }

    public function setDecideAt(DateTimeInterface $dateTime): void
    {
        $this->decideAt = $dateTime;
    }

    public function getDecideAt(): DateTimeInterface
    {
        return $this->decideAt;
    }
}
