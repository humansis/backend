<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ImportDuplicityState;
use Enum\ImportQueueState;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Entity\User;

/**
 * Imformation about duplicity between two queue records.
 *
 * @ORM\Entity()
 */
class ImportQueueDuplicity
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    /**

     * @ORM\Column(name="state", type="enum_import_duplicity_state", nullable=false)
     */
    private string $state;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User")
     */
    private ?\Entity\User $decideBy = null;

    /**
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=true)
     */
    private ?\DateTimeInterface $decideAt = null;

    public function __construct(/**
         * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importQueueDuplicitiesOurs")
         */
        private ImportQueue $ours, /**
         * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importQueueDuplicitiesTheirs")
         */
        private ImportQueue $theirs
    ) {
        $this->state = ImportDuplicityState::DUPLICITY_CANDIDATE;
    }

    public function getOurs(): ImportQueue
    {
        return $this->ours;
    }

    public function getTheirs(): ImportQueue
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
     * @see ImportDuplicityState::values()
     */
    public function setState(string $state)
    {
        self::validateValue('state', ImportDuplicityState::class, $state, false);
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
