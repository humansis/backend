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
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importQueueDuplicitiesOurs")
     */
    private $ours;

    /**
     * @var ImportQueue
     *
     * @ORM\ManyToOne(targetEntity="Entity\ImportQueue", inversedBy="importQueueDuplicitiesTheirs")
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
     * @ORM\ManyToOne(targetEntity="Entity\User")
     */
    private $decideBy;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=true)
     */
    private $decideAt;

    public function __construct(ImportQueue $ours, ImportQueue $theirs)
    {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = ImportDuplicityState::DUPLICITY_CANDIDATE;
    }

    /**
     * @return ImportQueue
     */
    public function getOurs(): ImportQueue
    {
        return $this->ours;
    }

    /**
     * @return ImportQueue
     */
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
     * @param DateTimeInterface $dateTime
     */
    public function setDecideAt(DateTimeInterface $dateTime): void
    {
        $this->decideAt = $dateTime;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDecideAt(): DateTimeInterface
    {
        return $this->decideAt;
    }
}
